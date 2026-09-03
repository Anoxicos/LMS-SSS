<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Company Request Updated</title>
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
    cellpadding="0"
    cellspacing="0"
    border="0"
    style="
        padding:40px 15px;
        background:#f4fbff;
    "
>
    <tr>
        <td align="center">

            <table
                width="100%"
                cellpadding="0"
                cellspacing="0"
                border="0"
                style="
                    width:100%;
                    max-width:650px;
                    background:#ffffff;
                    border:1px solid #e4edf4;
                    border-radius:16px;
                    overflow:hidden;
                "
            >

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
                                letter-spacing:1.5px;
                                text-transform:uppercase;
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
                            Company Request Updated
                        </h1>

                    </td>
                </tr>

                <tr>
                    <td
                        style="
                            padding:35px;
                        "
                    >

                        <p
                            style="
                                color:#64748b;
                                font-size:15px;
                                line-height:1.7;
                            "
                        >
                            The training request

                            <strong
                                style="color:#087bc9;"
                            >
                                {{ $companyRequest->reference }}
                            </strong>

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
                            </strong>

                            has been updated.
                        </p>

                        <div
                            style="
                                padding:18px 20px;
                                margin:25px 0;
                                background:#fff8e6;
                                border-left:4px solid #f7b84b;
                                border-radius:8px;
                            "
                        >
                            <strong
                                style="
                                    color:#8a6116;
                                "
                            >
                                Please review the updated
                                information before processing
                                this request.
                            </strong>
                        </div>

                        <table
                            width="100%"
                            cellpadding="0"
                            cellspacing="0"
                            border="0"
                            style="
                                border-collapse:collapse;
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
                                    Reference
                                </td>

                                <td
                                    style="
                                        padding:12px 0;
                                        border-bottom:1px solid #e4edf4;
                                        font-weight:bold;
                                        color:#087bc9;
                                    "
                                >
                                    {{ $companyRequest->reference }}
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
                                <strong>
                                    Notes
                                </strong>

                                <p
                                    style="
                                        margin-bottom:0;
                                        color:#64748b;
                                        line-height:1.6;
                                    "
                                >
                                    {{ $companyRequest->notes }}
                                </p>

                            </div>

                        @endif

                        <table
                            align="center"
                            cellpadding="0"
                            cellspacing="0"
                            border="0"
                            style="
                                margin-top:30px;
                            "
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
                                            font-weight:bold;
                                            font-size:14px;
                                            text-decoration:none;
                                        "
                                    >
                                        Review Updated Request
                                    </a>
                                </td>
                            </tr>
                        </table>

                    </td>
                </tr>

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