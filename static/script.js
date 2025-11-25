// --- CONFIGURATION ---
const API_BASE = "/api"; // Flask Backend

// --- ROUTER ---
function router(screenId) {
    document.querySelectorAll('section').forEach(el => el.classList.add('hidden-screen'));
    document.getElementById(screenId).classList.remove('hidden-screen');
}

function showToast(msg) {
    const t = document.getElementById('toast');
    document.getElementById('toast-msg').innerText = msg;
    t.classList.remove('translate-x-full');
    setTimeout(() => t.classList.add('translate-x-full'), 3000);
}

// --- AUTHENTICATION ---
async function handleRegistration() {
    const user = {
        name: document.getElementById('reg-name').value,
        age: parseInt(document.getElementById('reg-age').value),
        phone: document.getElementById('reg-phone').value,
        gender: document.getElementById('reg-gender').value,
        religion: document.getElementById('reg-religion').value,
        job: document.getElementById('reg-job').value,
        income: document.getElementById('reg-income').value,
        tier: 'FREE'
    };

    if (!user.phone || !user.name) return showToast("Please fill all details");

    const res = await fetch(`${API_BASE}/register`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(user)
    });

    if (res.ok) {
        localStorage.setItem('phone', user.phone);
        showToast("Welcome! AI is finding matches...");
        router('screen-dashboard');
        loadMatches();
    } else {
        showToast("Error creating profile");
    }
}

function logout() {
    localStorage.removeItem('phone');
    router('screen-landing');
}

// --- MAIN FEATURE: MATCHING ---
async function loadMatches() {
    const grid = document.getElementById('matches-grid');
    grid.innerHTML = '<div class="col-span-full text-center p-10"><i class="fa-solid fa-spinner fa-spin text-4xl text-pink-500"></i><p class="mt-4 text-gray-500">AI is analyzing 1,500+ profiles for compatibility...</p></div>';

    const phone = localStorage.getItem('phone');
    const res = await fetch(`${API_BASE}/matches`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ phone })
    });

    if (!res.ok) return router('screen-landing'); // Auth failed

    const matches = await res.json();
    grid.innerHTML = ''; // Clear loader

    if (matches.length === 0) {
        grid.innerHTML = '<div class="col-span-full text-center">No matches found yet.</div>';
        return;
    }

    matches.forEach(m => {
        const card = document.createElement('div');
        card.className = "bg-white rounded-xl shadow border border-gray-100 overflow-hidden hover:shadow-lg transition";
        card.innerHTML = `
            <div class="h-24 bg-gradient-to-r from-gray-200 to-gray-300 relative">
                <div class="absolute -bottom-6 left-4 h-16 w-16 rounded-full bg-white p-1 shadow">
                    <div class="h-full w-full rounded-full bg-pink-100 flex items-center justify-center text-xl font-bold text-pink-600">${m.name[0]}</div>
                </div>
                <span class="absolute top-2 right-2 bg-white/90 px-2 py-1 rounded text-xs font-bold text-green-600 shadow">
                    ${m.score}% Match
                </span>
            </div>
            <div class="pt-8 p-4">
                <h3 class="font-bold text-lg text-gray-800">${m.name}</h3>
                <p class="text-sm text-gray-500 mb-3">${m.age} Yrs • ${m.job} • ${m.religion}</p>
                
                <div class="bg-pink-50 text-pink-800 text-xs p-3 rounded-lg mb-4">
                    <i class="fa-solid fa-robot mr-1"></i> "${m.ai_reason}"
                </div>

                <div class="flex items-center justify-between text-sm border-t pt-3">
                    <span class="text-gray-600"><i class="fa-solid fa-phone mr-1"></i> ${m.phone}</span>
                </div>
            </div>
        `;
        grid.appendChild(card);
    });
}

// --- PAYMENTS (RAZORPAY) ---
async function upgradePlan() {
    const phone = localStorage.getItem('phone');
    
    // 1. Create Order
    const res = await fetch(`${API_BASE}/create-order`, { method: 'POST' });
    const order = await res.json();

    // 2. Open Payment Gateway
    const options = {
        "key": "YOUR_RAZORPAY_KEY_ID", // Put your Public Key here
        "amount": order.amount,
        "currency": "INR",
        "name": "WedLock Premium",
        "description": "Unlock Contact Numbers",
        "order_id": order.id,
        "handler": async function (response) {
            // 3. Verify on Backend
            const verifyRes = await fetch(`${API_BASE}/verify-payment`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    phone: phone,
                    razorpay_order_id: response.razorpay_order_id,
                    razorpay_payment_id: response.razorpay_payment_id,
                    razorpay_signature: response.razorpay_signature
                })
            });
            const result = await verifyRes.json();
            if(result.success) {
                alert("Upgrade Successful!");
                document.getElementById('user-badge').innerText = "GOLD";
                document.getElementById('user-badge').className = "text-xs font-bold bg-amber-100 text-amber-700 px-2 py-1 rounded border border-amber-200";
                loadMatches(); // Reload to unblur phones
            }
        }
    };
    const rzp = new Razorpay(options);
    rzp.open();
}

// --- ADMIN ---
async function handleAdminLogin() {
    const pass = document.getElementById('admin-pass').value;
    const res = await fetch(`${API_BASE}/admin-login`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ password: pass })
    });
    if(res.ok) {
        alert("Admin Logged In");
        // Redirect to admin dashboard (implement logic)
    } else {
        alert("Invalid Password");
    }
}


