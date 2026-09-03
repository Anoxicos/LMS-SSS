<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Course Is Ready</title>
</head>
<body style="margin:0;padding:0;background:#f4fbff;font-family:Arial,Helvetica,sans-serif;color:#071126;">
<table width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#f4fbff;padding:40px 15px;">
    <tr>
        <td align="center">
            <table width="100%" cellpadding="0" cellspacing="0" border="0" style="max-width:650px;background:#ffffff;border:1px solid #e4edf4;border-radius:16px;overflow:hidden;">
                <tr>
                    <td style="background:#071126;padding:30px 35px;text-align:center;">
                        <div style="color:#37cced;font-size:13px;font-weight:bold;letter-spacing:1.5px;text-transform:uppercase;margin-bottom:8px;">SafeSky LMS</div>
                        <h1 style="margin:0;color:#ffffff;font-size:25px;">Your Course Is Ready</h1>
                    </td>
                </tr>
                <tr>
                    <td style="padding:35px;">
                        <p style="margin-top:0;color:#64748b;font-size:15px;line-height:1.7;">Hello <strong style="color:#071126;">{{ $learner->full_name ?: $learner->email }}</strong>,</p>

                        <p style="color:#64748b;font-size:15px;line-height:1.7;">Good news. A new training course has been assigned to your SafeSky LMS account. You can sign in and start your training.</p>

                        <div style="margin:25px 0;padding:22px;background:#eaf7ff;border-left:4px solid #0d8fe9;border-radius:8px;">
                            <div style="color:#64748b;font-size:12px;text-transform:uppercase;letter-spacing:1px;margin-bottom:6px;">Assigned Course</div>
                            <div style="color:#071126;font-size:20px;font-weight:bold;line-height:1.4;">{{ $companyRequest->course?->title ?? '-' }}</div>
                            @if($companyRequest->course?->category?->name)
                                <div style="color:#087bc9;font-size:13px;margin-top:6px;">{{ $companyRequest->course->category->name }}</div>
                            @endif
                        </div>

                        <table width="100%" cellpadding="0" cellspacing="0" border="0" style="border-collapse:collapse;">
                            <tr>
                                <td style="padding:12px 0;border-bottom:1px solid #e4edf4;color:#64748b;width:38%;">Company</td>
                                <td style="padding:12px 0;border-bottom:1px solid #e4edf4;font-weight:bold;">{{ $companyRequest->company?->name ?? '-' }}</td>
                            </tr>
                            <tr>
                                <td style="padding:12px 0;border-bottom:1px solid #e4edf4;color:#64748b;">Start Date</td>
                                <td style="padding:12px 0;border-bottom:1px solid #e4edf4;">{{ $companyRequest->start_date ? \Illuminate\Support\Carbon::parse($companyRequest->start_date)->format('d/m/Y') : '-' }}</td>
                            </tr>
                            <tr>
                                <td style="padding:12px 0;border-bottom:1px solid #e4edf4;color:#64748b;">End Date</td>
                                <td style="padding:12px 0;border-bottom:1px solid #e4edf4;">{{ $companyRequest->end_date ? \Illuminate\Support\Carbon::parse($companyRequest->end_date)->format('d/m/Y') : '-' }}</td>
                            </tr>
                            @if($companyRequest->duree_validite_formation)
                                <tr>
                                    <td style="padding:12px 0;color:#64748b;">Validity</td>
                                    <td style="padding:12px 0;">{{ $companyRequest->duree_validite_formation }} month(s)</td>
                                </tr>
                            @endif
                        </table>

                        <div style="margin-top:25px;padding:16px;background:#f8fafc;border-radius:8px;color:#64748b;font-size:13px;line-height:1.7;">Sign in using the email address associated with your SafeSky LMS account, then open your learner space to start the course.</div>

                        <table align="center" cellpadding="0" cellspacing="0" border="0" style="margin-top:30px;">
                            <tr>
                                <td bgcolor="#071126" style="border-radius:8px;">
                                    <a href="{{ $courseUrl }}" style="display:inline-block;padding:14px 30px;color:#ffffff;text-decoration:none;font-size:14px;font-weight:bold;">Access SafeSky LMS</a>
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
