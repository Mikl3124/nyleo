<?php

namespace App\Http\Controllers;

use App\Models\ClientActivity;
use App\Models\EmailMessage;
use App\Services\ClientTracking;
use Illuminate\Http\Request;

/** Pixel d'ouverture et redirection des clics des e-mails clients. */
class MailTrackingController extends Controller
{
    /** GIF transparent de 1 × 1 pixel. */
    private const PIXEL = 'R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7';

    public function open(Request $request, string $token)
    {
        if ($email = EmailMessage::where('token', $token)->first()) {
            $activity = ClientTracking::record(ClientActivity::OPENED, 'E-mail « '.$email->subject.' » ouvert', ['email' => $email], $request);
            if ($activity) {
                $email->forceFill([
                    'opened_at' => $email->opened_at ?? now(),
                    'last_opened_at' => now(),
                    'open_count' => $email->open_count + 1,
                ])->save();
            }
        }

        return response(base64_decode(self::PIXEL), 200, [
            'Content-Type' => 'image/gif',
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma' => 'no-cache',
        ]);
    }

    public function click(Request $request, string $token, int $link)
    {
        $email = EmailMessage::where('token', $token)->firstOrFail();
        $target = $email->links[$link] ?? abort(404);

        $activity = ClientTracking::record(ClientActivity::CLICKED, 'Bouton « '.$target['label'].' » cliqué', ['email' => $email], $request);
        if ($activity) {
            $email->forceFill([
                'clicked_at' => $email->clicked_at ?? now(),
                'click_count' => $email->click_count + 1,
                // Un clic prouve l'ouverture, même si les images étaient bloquées.
                'opened_at' => $email->opened_at ?? now(),
                'last_opened_at' => $email->last_opened_at ?? now(),
            ])->save();
        }

        return redirect()->away($target['url']);
    }
}
