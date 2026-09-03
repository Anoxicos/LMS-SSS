<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>New Company Request</title>
</head>

<body
    style="
        margin:0;
        padding:0;
        background:#f4fbff;
        font-family:Arial,Helvetica,sans-serif;
        color:#071126;
    "
>

<table
    width="100%"
    cellspacing="0"
    cellpadding="0"
    border="0"
    style="
        background:#f4fbff;
        padding:40px 15px;
    "
>
    <tr>
        <td align="center">

            <table
                width="100%"
                cellspacing="0"
                cellpadding="0"
                border="0"
                style="
                    max-width:650px;
                    background:#ffffff;
                    border:1px solid #e4edf4;
                    border-radius:16px;
                    overflow:hidden;
                "
            >

                <!-- Header -->

                <tr>
                    <td
                        style="
                            background:#071126;
                            padding:28px 35px;
                            text-align:center;
                        "
                    >

                        <div
                            style="
                                color:#37cced;
                                font-size:13px;
                                font-weight:bold;
                                text-transform:uppercase;
                                letter-spacing:1.5px;
                                margin-bottom:8px;
                            "
                        >
                            SafeSky LMS
                        </div>

                        <h1
                            style="
                                margin:0;
                                color:#ffffff;
                                font-size:25px;
                            "
                        >
                            New Company Training Request
                        </h1>

                    </td>
                </tr>

                <!-- Body -->

                <tr>
                    <td
                        style="
                            padding:35px;
                        "
                    >

                        <p
                            style="
                                margin-top:0;
                                color:#64748b;
                                font-size:15px;
                                line-height:1.7;
                            "
                        >
                            Hello,
                        </p>

                        <p
                            style="
                                color:#64748b;
                                font-size:15px;
                                line-height:1.7;
                            "
                        >
                            A new training request has been
                            submitted by

                            <strong
                                style="color:#071126;"
                            >
                                {{
                                    $companyRequest
                                        ->company
                                        ?->name
                                        ?? 'Company'
                                }}
                            </strong>.

                            Please review the request details below.
                        </p>

                        <!-- Reference -->

                        <div
                            style="
                                margin:25px 0;
                                padding:18px 20px;
                                background:#eaf7ff;
                                border-left:4px solid #0d8fe9;
                                border-radius:8px;
                            "
                        >
                            <div
                                style="
                                    color:#64748b;
                                    font-size:12px;
                                    text-transform:uppercase;
                                    letter-spacing:1px;
                                "
                            >
                                Request Reference
                            </div>

                            <div
                                style="
                                    color:#087bc9;
                                    font-weight:bold;
                                    font-size:18px;
                                    margin-top:5px;
                                "
                            >
                                {{ $companyRequest->reference }}
                            </div>
                        </div>

                        <!-- Details -->

                        <table
                            width="100%"
                            cellpadding="0"
                            cellspacing="0"
                            border="0"
                            style="
                                border-collapse:collapse;
                                margin-top:25px;
                            "
                        >

                            <tr>
                                <td
                                    style="
                                        padding:12px 0;
                                        border-bottom:1px solid #e4edf4;
                                        color:#64748b;
                                        width:38%;
                                    "
                                >
                                    Company
                                </td>

                                <td
                                    style="
                                        padding:12px 0;
                                        border-bottom:1px solid #e4edf4;
                                        font-weight:bold;
                                    "
                                >
                                    {{
                                        $companyRequest
                                            ->company
                                            ?->name
                                            ?? '-'
                                    }}
                                </td>
                            </tr>

                            <tr>
                                <td
                                    style="
                                        padding:12px 0;
                                        border-bottom:1px solid #e4edf4;
                                        color:#64748b;
                                    "
                                >
                                    Course
                                </td>

                                <td
                                    style="
                                        padding:12px 0;
                                        border-bottom:1px solid #e4edf4;
                                        font-weight:bold;
                                    "
                                >
                                    {{
                                        $companyRequest
                                            ->course
                                            ?->title
                                            ?? '-'
                                    }}
                                </td>
                            </tr>

                            <tr>
                                <td
                                    style="
                                        padding:12px 0;
                                        border-bottom:1px solid #e4edf4;
                                        color:#64748b;
                                    "
                                >
                                    Requested By
                                </td>

                                <td
                                    style="
                                        padding:12px 0;
                                        border-bottom:1px solid #e4edf4;
                                    "
                                >
                                    {{
                                        $companyRequest
                                            ->requestedBy
                                            ?->full_name
                                            ?? '-'
                                    }}

                                    @if(
                                        $companyRequest
                                            ->requestedBy
                                            ?->email
                                    )
                                        <br>

                                        <span
                                            style="
                                                color:#94a3b8;
                                                font-size:13px;
                                            "
                                        >
                                            {{
                                                $companyRequest
                                                    ->requestedBy
                                                    ->email
                                            }}
                                        </span>
                                    @endif
                                </td>
                            </tr>

                            <tr>
                                <td
                                    style="
                                        padding:12px 0;
                                        border-bottom:1px solid #e4edf4;
                                        color:#64748b;
                                    "
                                >
                                    Training Period
                                </td>

                                <td
                                    style="
                                        padding:12px 0;
                                        border-bottom:1px solid #e4edf4;
                                    "
                                >
                                    {{
                                        $companyRequest->start_date
                                            ? \Illuminate\Support\Carbon::parse(
                                                $companyRequest->start_date
                                            )->format('d/m/Y')
                                            : '-'
                                    }}

                                    &nbsp; → &nbsp;

                                    {{
                                        $companyRequest->end_date
                                            ? \Illuminate\Support\Carbon::parse(
                                                $companyRequest->end_date
                                            )->format('d/m/Y')
                                            : '-'
                                    }}
                                </td>
                            </tr>

                            <tr>
                                <td
                                    style="
                                        padding:12px 0;
                                        border-bottom:1px solid #e4edf4;
                                        color:#64748b;
                                    "
                                >
                                    Learners
                                </td>

                                <td
                                    style="
                                        padding:12px 0;
                                        border-bottom:1px solid #e4edf4;
                                        font-weight:bold;
                                    "
                                >
                                    {{
                                        $companyRequest
                                            ->users
                                            ->count()
                                    }}
                                </td>
                            </tr>

                            <tr>
                                <td
                                    style="
                                        padding:12px 0;
                                        color:#64748b;
                                    "
                                >
                                    Accreditations
                                </td>

                                <td
                                    style="
                                        padding:12px 0;
                                    "
                                >
                                    {{
                                        $companyRequest
                                            ->accreditations
                                            ->pluck('name')
                                            ->implode(', ')
                                            ?: '-'
                                    }}
                                </td>
                            </tr>

                        </table>

                        @if($companyRequest->notes)

                            <div
                                style="
                                    margin-top:25px;
                                    padding:18px;
                                    background:#f8fafc;
                                    border-radius:8px;
                                "
                            >
                                <div
                                    style="
                                        color:#071126;
                                        font-weight:bold;
                                        margin-bottom:8px;
                                    "
                                >
                                    Notes
                                </div>

                                <div
                                    style="
                                        color:#64748b;
                                        line-height:1.6;
                                    "
                                >
                                    {{ $companyRequest->notes }}
                                </div>

                            </div>

                        @endif

                        <!-- CTA -->

                        <table
                            cellpadding="0"
                            cellspacing="0"
                            border="0"
                            align="center"
                            style="margin-top:30px;"
                        >
                            <tr>
                                <td
                                    bgcolor="#071126"
                                    style="
                                        border-radius:8px;
                                    "
                                >
                                    <a
                                        href="{{ $url }}"
                                        style="
                                            display:inline-block;
                                            padding:14px 28px;
                                            color:#ffffff;
                                            text-decoration:none;
                                            font-size:14px;
                                            font-weight:bold;
                                        "
                                    >
                                        Review Request
                                    </a>
                                </td>
                            </tr>
                        </table>

                    </td>
                </tr>

                <!-- Footer -->

                <tr>
                    <td
                        style="
                            padding:20px;
                            background:#071126;
                            text-align:center;
                            color:#94a3b8;
                            font-size:12px;
                        "
                    >
                        © {{ date('Y') }}
                        SafeSky Solution.
                        Learning Management System.
                    </td>
                </tr>

            </table>

        </td>
    </tr>
</table>

</body>
</html>