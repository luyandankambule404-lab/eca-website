<?php
ob_start();

require_once "config.php";
require_once __DIR__ . "/dompdf/autoload.inc.php";
require_once __DIR__ . '/phpqrcode/qrlib.php';

use Dompdf\Dompdf;
use Dompdf\Options;

error_reporting(0);

/* =================================
CHECK ID
================================= */

if (!isset($_GET['id'])) {
    exit("No attendee ID provided.");
}

$id = intval($_GET['id']);

/* =================================
GET ATTENDEE + COURSE
================================= */

$result = $conn->query("
SELECT 
a.*,
c.title,
c.description,
c.start_date,
c.end_date,
c.venue,
c.points
FROM cpd_applications a
JOIN courses c ON c.id = a.course_id
WHERE a.id = $id
");

if ($result->num_rows === 0) {
    exit("Attendee not found.");
}

$attendee = $result->fetch_assoc();

/* =================================
CERTIFICATE NUMBER
================================= */

$certNumber = strtoupper(uniqid("ECA-CPD-"));

$stmt = $conn->prepare("
UPDATE cpd_applications
SET certificate_number = ?
WHERE id = ?
");

$stmt->bind_param("si", $certNumber, $id);
$stmt->execute();

/* =================================
GENERATE QR CODE
================================= */

$qrDir = __DIR__ . "/certificates/qrcodes/";

if (!is_dir($qrDir)) {
    mkdir($qrDir, 0777, true);
}

$verifyURL = "https://eca.co.sz/portal/training_verify.php?cert=" . urlencode($certNumber);

$qrFile = $qrDir . "qr_" . $id . ".png";

QRcode::png($verifyURL, $qrFile, QR_ECLEVEL_L, 4);

/* =================================
IMAGES
================================= */

$logoPath = realpath(__DIR__ . "/image/logo.png");
$signChairPath = realpath(__DIR__ . "/image/signature12.png");

$qrBase64 = base64_encode(file_get_contents($qrFile));
$logoBase64 = base64_encode(file_get_contents($logoPath));
$signChairBase64 = base64_encode(file_get_contents($signChairPath));

/* =================================
FORMAT DATES
================================= */

$startDate = date("d F Y", strtotime($attendee['start_date']));
$endDate   = date("d F Y", strtotime($attendee['end_date']));

/* =================================
DOMPDF SETUP
================================= */

$options = new Options();
$options->set('isRemoteEnabled', true);

$dompdf = new Dompdf($options);

/* =================================
CERTIFICATE HTML
================================= */

$html = '

<!DOCTYPE html>
<html>
<head>

<style>

body{

font-family: "Times New Roman", serif;

background: url("https://eca.co.sz/portal/image/Certificate1.png") no-repeat center center;

background-size: cover;

text-align:center;

color:#000;

}

h1{

font-size:36px;

color:#002060;

margin-bottom:10px;

text-transform:uppercase;

}

.name{

font-size:30px;

font-weight:bold;

margin:15px 0;

}

.course{

font-size:18px;

font-weight:bold;

color:#000066;

}

p{

font-size:14px;

line-height:1.6;

}

.signature-line{

border-bottom:1px solid #000;

width:60%;

margin:8px auto;

}

</style>

</head>

<body>

<br><br><br><br>

<h1>Attendance Certificate</h1>

<p style="font-size:18px">This certificate is proudly presented to</p>

<div class="name">

' . htmlspecialchars($attendee['full_name']) . '

<div class="signature-line"></div>

</div>

<p style="font-size:16px">

For successfully attending the training programme:

</p>

<div class="course">

' . htmlspecialchars($attendee['title']) . '

</div>

<br>

<p style="width:70%; margin:auto">

' . nl2br(htmlspecialchars($attendee['description'])) . '

</p>



<p>

Date: <b>' . $attendee['end_date'] . '</b>   Awarded <b>' . $attendee['points'] . ' CPD Points</b>

</p>


<table style="width:100%; margin-top:40px; text-align:center;">

<tr>

<td style="width:33%">

<p>

Certificate Number<br>

<b>' . $certNumber . '</b>

</p>

<img src="data:image/png;base64,' . $qrBase64 . '" width="90">

</td>

<td style="width:33%"></td>

<td style="width:33%">

<img src="data:image/png;base64,' . $signChairBase64 . '" width="120">

<div class="signature-line"></div>

<strong>Nolwazi Dlamini</strong><br>

ECA CEO

</td>

</tr>

</table>

</body>

</html>

';

/* =================================
GENERATE PDF
================================= */

$dompdf->loadHtml($html);

$dompdf->setPaper('A4', 'landscape');

$dompdf->render();

/* =================================
SAVE PDF
================================= */

$pdfDir = __DIR__ . "/certificates/";

if (!is_dir($pdfDir)) {
    mkdir($pdfDir, 0777, true);
}

$pdfFile = $pdfDir . "certificate_" . $id . ".pdf";

file_put_contents($pdfFile, $dompdf->output());

/* =================================
STREAM TO BROWSER
================================= */

ob_end_clean();

$dompdf->stream(
"ECA_Certificate_" . $attendee['attendee_name'] . ".pdf",
["Attachment" => false]
);

exit;

?>