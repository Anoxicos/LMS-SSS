<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Company Request Rejected</title>
</head>
<body style="margin:0;padding:0;background:#f4fbff;font-family:Arial,Helvetica,sans-serif;color:#071126;">
<table width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#f4fbff;padding:40px 15px;">
    <tr>
        <td align="center">
            <table width="100%" cellpadding="0" cellspacing="0" border="0" style="max-width:650px;background:#ffffff;border:1px solid #e4edf4;border-radius:16px;overflow:hidden;">
                <tr>
                    <td style="background:#071126;padding:30px 35px;text-align:center;">
                        <div style="color:#37cced;font-size:13px;font-weight:bold;letter-spacing:1.5px;text-transform:uppercase;margin-bottom:8px;">SafeSky LMS</div>
                        <h1 style="margin:0;color:#ffffff;font-size:25px;">Training Request Update</h1>
                    </td>
                </tr>
                <tr>
                    <td style="padding:35px;">
                        <div style="width:64px;height:64px;line-height:64px;text-align:center;margin:0 auto 24px;background:#fff0ed;color:#f06548;border-radius:50%;font-size:28px;">&#10005;</div>

                        <p style="margin-top:0;color:#64748b;font-size:15px;line-height:1.7;">Hello <strong style="color:#071126;">{{ $companyRequest->company?->name ?? 'Company' }}</strong>,</p>

                        <p style="color:#64748b;font-size:15px;line-height:1.7;">Your training request has been <strong style="color:#f06548;">rejected</strong>. Please review the request information and the notes below.</p>

                        <div style="margin:25px 0;padding:18px 20px;background:#fff0ed;border-left:4px solid #f06548;border-radius:8px;">
                            <div style="color:#64748b;font-size:12px;text-transform:uppercase;letter-spacing:1px;">Request Reference</div>
                            <div style="color:#071126;font-weight:bold;font-size:18px;margin-top:5px;">{{ $companyRequest->reference }}</div>
                        </div>

                        <table width="100%" cellpadding="0" cellspacing="0" border="0" style="border-collapse:collapse;">
                            <tr>
                                <td style="padding:12px 0;border-bottom:1px solid #e4edf4;color:#64748b;width:38%;">Course</td>
                                <td style="padding:12px 0;border-bottom:1px solid #e4edf4;font-weight:bold;">{{ $companyRequest->course?->title ?? '-' }}</td>
                            </tr>
                            <tr>
                                <td style="padding:12px 0;border-bottom:1px solid #e4edf4;color:#64748b;">Status</td>
                                <td style="padding:12px 0;border-bottom:1px solid #e4edf4;font-weight:bold;color:#f06548;">Rejected</td>
                            </tr>
                        </table>

                        @if($companyRequest->notes)
                            <div style="margin-top:25px;padding:18px;background:#f8fafc;border-radius:8px;border:1px solid #e4edf4;">
                                <div style="font-weight:bold;color:#071126;margin-bottom:8px;">Notes</div>
                                <div style="color:#64748b;line-height:1.7;">{{ $companyRequest->notes }}</div>
                            </div>
                        @endif

                        <table align="center" cellpadding="0" cellspacing="0" border="0" style="margin-top:30px;">
                            <tr>
                                <td bgcolor="#071126" style="border-radius:8px;">
                                    <a href="{{ $url }}" style="display:inline-block;padding:14px 28px;color:#ffffff;text-decoration:none;font-size:14px;font-weight:bold;">View Request</a>
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
