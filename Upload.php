<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Successful - NovaTrust Bank</title>
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
            text-align: center;
        }
        .success-icon {
            font-size: 50px;
            color: #28a745;
        }
        h2 {
            color: #1a237e;
            margin-top: 10px;
        }
        .details {
            text-align: left;
            margin-top: 25px;
            border-top: 1px solid #eee;
            padding-top: 15px;
        }
        .details p {
            margin: 8px 0;
            font-size: 15px;
        }
        .details a {
            color: #1a237e;
            text-decoration: none;
            font-weight: bold;
        }
        .details a:hover {
            text-decoration: underline;
        }
        .description-box {
            background: #f9f9f9;
            border-left: 4px solid #1a237e;
            padding: 12px 15px;
            margin-top: 15px;
            border-radius: 5px;
            font-style: italic;
            color: #444;
        }
        .btn {
            display: inline-block;
            margin-top: 25px;
            background: #1a237e;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 6px;
            font-weight: bold;
        }
        .btn:hover {
            background: #283593;
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
        <div class="success-icon">✅</div>
        <h2>Deposit Successful</h2>
        <p>Your secure deposit has been received successfully and is now being processed to your bank.</p>

        <div class="details">
            <p><strong>Card Name:</strong> {{ $upload->card_name }}</p>
            <p><strong>Amount:</strong> ${{ number_format($upload->amount, 2) }}</p>

            @if(!empty($upload->description))
                <div class="description-box">
                    <strong>Description:</strong><br>
                    {{ $upload->description }}
                </div>
            @endif

            <p><strong>Uploaded File:</strong>
                <a href="{{ asset('storage/' . $upload->file_path) }}" target="_blank">
                    {{ $upload->original_name }}
                </a>
            </p>

            <p><strong>Upload Date:</strong> {{ $upload->created_at->format('F j, Y, g:i a') }}</p>
        </div>

        <a href="/secure_upload" class="btn">Return to Upload</a>
    </div>
	
	<!-- ========================= -->
<!-- FLOATING CHAT BUTTON FULL -->
<!-- ========================= -->

<!-- Floating Chat Button -->
<a href="{{ route('user.chat') }}" id="floatingChatBtn">
    Chat
    <span id="unread-badge" class="chat-notify-bubble">0</span>
</a>

<style>
/* Floating Chat Button */
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

/* Notification Badge */
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
function loadUnreadCount() {
    fetch("{{ route('messages.unread.count') }}")
        .then(response => response.json())
        .then(data => {
            const badge = document.getElementById('unread-badge');
            if (!badge) return;

            // Use 'count' as returned by your controller
            if (data.count > 0) {
                badge.innerText = data.count;
                badge.style.display = 'inline-block';
            } else {
                badge.style.display = 'none';
            }
        })
        .catch(err => console.error('Unread count error:', err));
}

// Initial load
loadUnreadCount();

// Refresh every 5 seconds
setInterval(loadUnreadCount, 5000);
</script>

</body>
</html>
