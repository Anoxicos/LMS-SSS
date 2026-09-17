<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Training Certificate</title>
</head>
<body style="margin:0;padding:0;background:#f4fbff;font-family:Arial,Helvetica,sans-serif;color:#071126;">
<table width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#f4fbff;padding:40px 15px;">
    <tr>
        <td align="center">
            <table width="100%" cellpadding="0" cellspacing="0" border="0" style="max-width:680px;background:#ffffff;border:1px solid #e4edf4;border-radius:16px;overflow:hidden;">
                <tr>
                    <td style="background:#071126;padding:32px 35px;text-align:center;">
                        <div style="color:#37cced;font-size:13px;font-weight:bold;letter-spacing:1.5px;text-transform:uppercase;margin-bottom:8px;">SafeSky LMS</div>
                        <h1 style="margin:0;color:#ffffff;font-size:26px;">Congratulations!</h1>
                    </td>
                </tr>
                <tr>
                    <td style="padding:35px;">
                        <p style="margin-top:0;color:#64748b;font-size:15px;line-height:1.7;">
                            Hello <strong style="color:#071126;">{{ $learner->full_name ?: $learner->email }}</strong>,
                        </p>

                        <p style="color:#64748b;font-size:15px;line-height:1.7;">
                            Congratulations. You successfully passed your final exam and completed your training.
                        </p>

                        <div style="margin:25px 0;padding:22px;background:#eaf7ff;border-left:4px solid #0d8fe9;border-radius:8px;">
                            <div style="color:#64748b;font-size:12px;text-transform:uppercase;letter-spacing:1px;margin-bottom:6px;">Course</div>
                            <div style="color:#071126;font-size:20px;font-weight:bold;">{{ $enrollment->course?->title ?? '-' }}</div>
                        </div>

                        <table width="100%" cellpadding="0" cellspacing="0" border="0" style="border-collapse:collapse;">
                            <tr>
                                <td style="padding:12px 0;border-bottom:1px solid #e4edf4;color:#64748b;width:38%;">Final Score</td>
                                <td style="padding:12px 0;border-bottom:1px solid #e4edf4;font-weight:bold;">{{ number_format((float) $exam->score, 2) }}%</td>
                            </tr>
                            <tr>
                                <td style="padding:12px 0;border-bottom:1px solid #e4edf4;color:#64748b;">Certificate(s)</td>
                                <td style="padding:12px 0;border-bottom:1px solid #e4edf4;">{{ $certificatesCount }}</td>
                            </tr>
                            @if($enrollment->expired_at)
                                <tr>
                                    <td style="padding:12px 0;color:#64748b;">Valid Until</td>
                                    <td style="padding:12px 0;">{{ \Illuminate\Support\Carbon::parse($enrollment->expired_at)->format('d/m/Y') }}</td>
                                </tr>
                            @endif
                        </table>

                        <div style="margin-top:25px;padding:16px;background:#f8fafc;border-radius:8px;color:#64748b;font-size:13px;line-height:1.7;">
                            Your official certificate{{ $certificatesCount > 1 ? 's are' : ' is' }} attached to this email as PDF.
                        </div>

                        <table align="center" cellpadding="0" cellspacing="0" border="0" style="margin-top:30px;">
                            <tr>
                                <td bgcolor="#071126" style="border-radius:8px;">
                                    <a href="{{ $loginUrl }}" style="display:inline-block;padding:14px 30px;color:#ffffff;text-decoration:none;font-size:14px;font-weight:bold;">Access SafeSky LMS</a>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
                <tr>
                    <td style="background:#071126;padding:20px;text-align:center;color:#94a3b8;font-size:12px;">&copy; {{ date('Y') }} SafeSky Solution. Learning Management System.</td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>
