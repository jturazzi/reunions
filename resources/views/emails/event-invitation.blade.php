<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $event->title }}</title>
</head>
<body style="margin:0;padding:0;background:#f4f6f9;font-family:Calibri,Arial,sans-serif;font-size:15px;color:#333">

<table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f6f9;padding:32px 0">
<tr><td align="center">
<table width="600" cellpadding="0" cellspacing="0" style="background:#fff;border-radius:10px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,.08)">

    {{-- En-tête --}}
    <tr>
        <td style="background:#4f46e5;padding:28px 36px">
            <p style="margin:0;font-size:13px;color:#a5b4fc;text-transform:uppercase;letter-spacing:.08em;font-weight:600">
                @if($method === 'CANCEL')
                    Annulation
                @elseif($event->ics_sequence > 0)
                    Mise à jour
                @else
                    Invitation
                @endif
            </p>
            <h1 style="margin:6px 0 0;font-size:22px;color:#fff;font-weight:700">
                {{ $event->title }}
            </h1>
        </td>
    </tr>

    {{-- Corps --}}
    <tr>
        <td style="padding:32px 36px">

            @if($method === 'CANCEL')
                <p style="margin:0 0 20px;font-size:15px;color:#b91c1c;font-weight:600">
                    Cet événement a été annulé.
                </p>
                <p style="margin:0 0 24px;color:#555">
                    Votre agenda sera mis à jour automatiquement lorsque vous accepterez cette annulation.
                </p>
            @else
                <p style="margin:0 0 24px;color:#555">
                    Bonjour {{ $recipient->name }},<br><br>
                    @if($event->ics_sequence > 0)
                        Cet événement a été <strong>mis à jour</strong>. Veuillez mettre à jour votre agenda.
                    @else
                        Vous êtes invité(e) à participer à l'événement suivant.
                    @endif
                </p>
            @endif

            {{-- Détails --}}
            <table width="100%" cellpadding="0" cellspacing="0" style="border:1px solid #e5e7eb;border-radius:8px;overflow:hidden;margin-bottom:24px">
                <tr style="background:#f9fafb">
                    <td style="padding:12px 16px;font-weight:600;width:130px;color:#374151;border-bottom:1px solid #e5e7eb">Date</td>
                    <td style="padding:12px 16px;color:#555;border-bottom:1px solid #e5e7eb">
                        {{ $event->start_at->translatedFormat('l d MMMM Y') }}
                    </td>
                </tr>
                <tr>
                    <td style="padding:12px 16px;font-weight:600;color:#374151;border-bottom:1px solid #e5e7eb">Horaires</td>
                    <td style="padding:12px 16px;color:#555;border-bottom:1px solid #e5e7eb">
                        {{ $event->start_at->format('H\hi') }} – {{ $event->end_at->format('H\hi') }}
                    </td>
                </tr>
                @if($event->location)
                <tr style="background:#f9fafb">
                    <td style="padding:12px 16px;font-weight:600;color:#374151;border-bottom:1px solid #e5e7eb">Lieu</td>
                    <td style="padding:12px 16px;color:#555;border-bottom:1px solid #e5e7eb">{{ $event->location }}</td>
                </tr>
                @endif
                @if($event->room_name)
                <tr>
                    <td style="padding:12px 16px;font-weight:600;color:#374151;border-bottom:1px solid #e5e7eb">Salle</td>
                    <td style="padding:12px 16px;color:#555;border-bottom:1px solid #e5e7eb">{{ $event->room_name }}</td>
                </tr>
                @endif
                @if($event->description)
                <tr @if(!$event->location && !$event->room_name) style="background:#f9fafb" @endif>
                    <td style="padding:12px 16px;font-weight:600;color:#374151;vertical-align:top">Description</td>
                    <td style="padding:12px 16px;color:#555">{{ $event->description }}</td>
                </tr>
                @endif
            </table>

            @if($method !== 'CANCEL')
                <p style="margin:0;font-size:13px;color:#6b7280;border-top:1px solid #e5e7eb;padding-top:20px">
                    Le fichier <strong>invitation.ics</strong> joint vous permet d'accepter ou refuser directement depuis Outlook.
                </p>
            @endif
        </td>
    </tr>

    {{-- Pied --}}
    <tr>
        <td style="background:#f9fafb;border-top:1px solid #e5e7eb;padding:16px 36px">
            <p style="margin:0;font-size:12px;color:#9ca3af">
                {{ config('mail.from.name') }} — Ce message est généré automatiquement.
            </p>
        </td>
    </tr>

</table>
</td></tr>
</table>
</body>
</html>
