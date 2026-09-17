<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Learner Certificate</title>
</head>
<body style="margin:0;padding:0;background:#f4fbff;font-family:Arial,Helvetica,sans-serif;color:#071126;">
<table width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#f4fbff;padding:40px 15px;">
    <tr>
        <td align="center">
            <table width="100%" cellpadding="0" cellspacing="0" border="0" style="max-width:680px;background:#ffffff;border:1px solid #e4edf4;border-radius:16px;overflow:hidden;">
                <tr>
                    <td style="background:#071126;padding:32px 35px;text-align:center;">
                        <div style="color:#37cced;font-size:13px;font-weight:bold;letter-spacing:1.5px;text-transform:uppercase;margin-bottom:8px;">SafeSky LMS</div>
                        <h1 style="margin:0;color:#ffffff;font-size:25px;">Learner Training Completed</h1>
                    </td>
                </tr>
                <tr>
                    <td style="padding:35px;">
                        <p style="margin-top:0;color:#64748b;font-size:15px;line-height:1.7;">
                            @if($companyName)
                                Hello <strong style="color:#071126;">{{ $companyName }}</strong>,
                            @else
                                Hello,
                            @endif
                        </p>

                        <p style="color:#64748b;font-size:15px;line-height:1.7;">
                            We are pleased to inform you that the learner below successfully passed the final exam and completed the assigned training.
                        </p>

                        <table width="100%" cellpadding="0" cellspacing="0" border="0" style="border-collapse:collapse;margin-top:20px;">
                            <tr>
                                <td style="padding:12px 0;border-bottom:1px solid #e4edf4;color:#64748b;width:38%;">Learner</td>
                                <td style="padding:12px 0;border-bottom:1px solid #e4edf4;font-weight:bold;">{{ $learner->full_name ?: $learner->email }}</td>
                            </tr>
                            <tr>
                                <td style="padding:12px 0;border-bottom:1px solid #e4edf4;color:#64748b;">Email</td>
                                <td style="padding:12px 0;border-bottom:1px solid #e4edf4;">{{ $learner->email }}</td>
                            </tr>
                            <tr>
                                <td style="padding:12px 0;border-bottom:1px solid #e4edf4;color:#64748b;">Course</td>
                                <td style="padding:12px 0;border-bottom:1px solid #e4edf4;font-weight:bold;">{{ $enrollment->course?->title ?? '-' }}</td>
                            </tr>
                            <tr>
                                <td style="padding:12px 0;border-bottom:1px solid #e4edf4;color:#64748b;">Final Score</td>
                                <td style="padding:12px 0;border-bottom:1px solid #e4edf4;">{{ number_format((float) $exam->score, 2) }}%</td>
                            </tr>
                            <tr>
                                <td style="padding:12px 0;color:#64748b;">Certificate(s)</td>
                                <td style="padding:12px 0;">{{ $certificatesCount }}</td>
                            </tr>
                        </table>

                        <div style="margin-top:25px;padding:16px;background:#eaf7ff;border-left:4px solid #0d8fe9;border-radius:8px;color:#64748b;font-size:13px;line-height:1.7;">
                            The learner's official certificate{{ $certificatesCount > 1 ? 's are' : ' is' }} attached to this email as PDF.
                        </div>
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
