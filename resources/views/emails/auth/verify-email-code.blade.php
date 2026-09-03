<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        Verify Your Email
    </title>
</head>

<body
    style="
        margin: 0;
        padding: 0;
        background-color: #f4fbff;
        font-family: Arial, Helvetica, sans-serif;
        color: #071126;
    "
>

<table
    width="100%"
    cellpadding="0"
    cellspacing="0"
    border="0"
    style="
        width: 100%;
        background-color: #f4fbff;
        padding: 40px 15px;
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
                    width: 100%;
                    max-width: 600px;
                    background: #ffffff;
                    border-radius: 16px;
                    overflow: hidden;
                    border: 1px solid #e4edf4;
                "
            >

                <tr>
                    <td
                        style="
                            height: 7px;
                            background:
                                linear-gradient(
                                    90deg,
                                    #37cced,
                                    #0d8fe9
                                );
                        "
                    ></td>
                </tr>

                <tr>
                    <td
                        style="
                            padding: 40px;
                            text-align: center;
                        "
                    >

                        <h1
                            style="
                                margin: 0 0 20px;
                                color: #071126;
                                font-size: 26px;
                            "
                        >
                            Verify Your Email
                        </h1>

                        <p
                            style="
                                color: #64748b;
                                font-size: 15px;
                                line-height: 1.7;
                                margin-bottom: 25px;
                            "
                        >
                            Hello
                            <strong>
                                {{ $user->full_name }}
                            </strong>,
                            <br>

                            Use the verification code below
                            to confirm your email address
                            and activate your SafeSky LMS
                            account.
                        </p>

                        <div
                            style="
                                display: inline-block;
                                background: #eaf7ff;
                                border: 1px solid #bde8ff;
                                border-radius: 12px;
                                padding: 20px 30px;
                                margin: 10px 0 25px;
                            "
                        >
                            <span
                                style="
                                    color: #087bc9;
                                    font-size: 34px;
                                    font-weight: 700;
                                    letter-spacing: 8px;
                                "
                            >
                                {{ $code }}
                            </span>
                        </div>

                        <p
                            style="
                                color: #64748b;
                                font-size: 14px;
                                line-height: 1.6;
                            "
                        >
                            This code is valid for
                            <strong>
                                {{ $expirationMinutes }} minutes
                            </strong>.
                        </p>

                        <p
                            style="
                                color: #94a3b8;
                                font-size: 13px;
                                line-height: 1.6;
                                margin-top: 30px;
                            "
                        >
                            If you did not create or modify
                            a SafeSky LMS account, you can
                            safely ignore this email.
                        </p>

                    </td>
                </tr>

                <tr>
                    <td
                        style="
                            background-color: #071126;
                            padding: 20px;
                            text-align: center;
                            color: #ffffff;
                            font-size: 12px;
                        "
                    >
                        © {{ date('Y') }}
                        SafeSky Solution.
                        All rights reserved.
                    </td>
                </tr>

            </table>

        </td>
    </tr>
</table>

</body>
</html>