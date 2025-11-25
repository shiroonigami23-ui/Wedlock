import os
import datetime
import razorpay
import firebase_admin
from firebase_admin import credentials, firestore
from flask import Flask, request, jsonify, send_from_directory, session
import google.generativeai as genai
from werkzeug.security import check_password_hash, generate_password_hash

app = Flask(__name__, static_folder='static')
# USE A REAL SECRET KEY IN PRODUCTION
app.secret_key = os.environ.get("FLASK_SECRET_KEY", "dev_key_only_for_local")

# --- CONFIGURATION ---

# 1. SETUP FIREBASE
# We check if the file exists (Local) or if we need to use a Secret File (Render)
firebase_cred_path = "wedlock-key.json"
if not firebase_admin._apps:
    if os.path.exists(firebase_cred_path):
        cred = credentials.Certificate(firebase_cred_path)
        firebase_admin.initialize_app(cred)
    else:
        # Fallback or error logging
        print("Error: Firebase Key not found!")

db = firestore.client()

# 2. SETUP GEMINI AI (Read from Environment)
GENAI_KEY = os.environ.get("GEMINI_API_KEY")
genai.configure(api_key=GENAI_KEY)
model = genai.GenerativeModel('gemini-1.5-flash')

# 3. SETUP RAZORPAY (Read from Environment)
RAZORPAY_ID = os.environ.get("RAZORPAY_KEY_ID")
RAZORPAY_SECRET = os.environ.get("RAZORPAY_KEY_SECRET")
razorpay_client = razorpay.Client(auth=(RAZORPAY_ID, RAZORPAY_SECRET))

# --- ROUTES ---

@app.route('/')
def home():
    return send_from_directory('static', 'index.html')

# --- API: USERS ---

@app.route('/api/register', methods=['POST'])
def register():
    data = request.json
    # Basic server-side validation
    if not data.get('phone') or not data.get('name'):
        return jsonify({"error": "Missing fields"}), 400
    
    # Save to Firebase
    db.collection('users').document(data['phone']).set(data)
    return jsonify({"success": True, "msg": "Profile Created!"})

@app.route('/api/matches', methods=['POST'])
def get_matches():
    user_phone = request.json.get('phone')
    if not user_phone:
        return jsonify({"error": "Not logged in"}), 401
        
    me_doc = db.collection('users').document(user_phone).get()
    if not me_doc.exists:
        return jsonify({"error": "User not found"}), 404
    me = me_doc.to_dict()
    
    # Logic: Find opposite gender
    target_gender = 'Female' if me['gender'] == 'Male' else 'Male'
    candidates = db.collection('users').where('gender', '==', target_gender).stream()
    
    results = []
    for c in candidates:
        profile = c.to_dict()
        if profile['phone'] == me['phone']: continue # Skip self

        # OPTIMIZED AI PROMPT
        prompt = f"""
        Act as an Indian Matchmaker. Compare:
        Me: {me['job']}, {me['age']}y, {me['religion']}, Income: {me['income']}
        Match: {profile['job']}, {profile['age']}y, {profile['religion']}, Income: {profile['income']}
        
        Output strictly as: SCORE|ONE_SHORT_REASON
        Example: 85|Great career match but age gap is high.
        """
        
        try:
            # 1.5 Flash is fast enough to do this in real-time
            response = model.generate_content(prompt)
            text = response.text.strip()
            score, reason = text.split('|')
        except:
            score = 70
            reason = "Profiles look compatible based on basic details."

        profile['score'] = int(score)
        profile['ai_reason'] = reason
        
        # Privacy Filter: Hide phone if user is FREE
        if me.get('tier', 'FREE') == 'FREE':
            profile['phone'] = "+91 9XXXX XXXXX (Upgrade to view)"
            
        results.append(profile)

    # Sort matches by AI Score
    results.sort(key=lambda x: x['score'], reverse=True)
    return jsonify(results)

# --- API: PAYMENTS (The Money Maker) ---

@app.route('/api/create-order', methods=['POST'])
def create_order():
    amount = 2900 # Rs 29.00 (Razorpay takes amount in paise)
    data = { "amount": amount, "currency": "INR", "receipt": "order_rcptid_11" }
    order = razorpay_client.order.create(data=data)
    return jsonify(order)

@app.route('/api/verify-payment', methods=['POST'])
def verify_payment():
    data = request.json
    # Verify signature to prevent hacking
    try:
        razorpay_client.utility.verify_payment_signature({
            'razorpay_order_id': data['razorpay_order_id'],
            'razorpay_payment_id': data['razorpay_payment_id'],
            'razorpay_signature': data['razorpay_signature']
        })
        
        # Update User to GOLD
        user_phone = data['phone']
        db.collection('users').document(user_phone).update({'tier': 'GOLD'})
        
        return jsonify({"success": True, "msg": "Upgraded to Gold!"})
    except:
        return jsonify({"success": False, "msg": "Payment Verification Failed"}), 400

# --- API: ADMIN (Secure) ---

@app.route('/api/admin-login', methods=['POST'])
def admin_login():
    password = request.json.get('password')
    # Use a secure password check (for demo: 'admin123')
    if password == "admin123": 
        session['is_admin'] = True
        return jsonify({"success": True})
    return jsonify({"error": "Wrong Password"}), 401

@app.route('/api/admin-stats')
def admin_stats():
    if not session.get('is_admin'):
        return jsonify({"error": "Unauthorized"}), 403
        
    users = db.collection('users').stream()
    user_list = [u.to_dict() for u in users]
    return jsonify({"count": len(user_list), "users": user_list})

if __name__ == '__main__':
    app.run(debug=True)
