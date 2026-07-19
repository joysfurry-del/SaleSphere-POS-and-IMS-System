<?php
/**
 * FPDF - PDF generation class (lightweight)
 * Generates valid PDF files with proper structure
 */
class FPDF {
    protected $page = 0;
    protected $pages = [];
    protected $buffer = '';
    protected $state = 0;
    protected $k = 2.834645669; // mm to points: 72 / 25.4
    protected $fw = 210;
    protected $fh = 297;
    protected $w;
    protected $h;
    protected $tMargin = 10;
    protected $bMargin = 20;
    protected $cMargin = 10;
    protected $x = 0;
    protected $y = 0;
    protected $lMargin = 10;
    protected $rMargin = 10;
    protected $AutoPageBreak = true;
    protected $PageBreakTrigger = 0;
    protected $InHeader = false;
    protected $InFooter = false;
    protected $ZoomMode = 'fullwidth';
    protected $LayoutMode = 'single';
    protected $title = '';
    protected $subject = '';
    protected $author = '';
    protected $keywords = '';
    protected $creator = 'FPDF';
    protected $AliasNbPages = '{nb}';
    protected $PDFVersion = '1.3';
    protected $FontFamily = 'Helvetica';
    protected $FontStyle = '';
    protected $FontSizePt = 12;
    protected $DrawColor = '0 G';
    protected $FillColor = '0 g';
    protected $TextColor = '0 g';
    protected $ColorFlag = false;
    protected $ws = 0;
    protected $images = [];
    protected $PageLinks = [];
    protected $links = [];
    protected $FontFiles = [];
    protected $diffs = [];
    protected $FontSizes = [];
    protected $currentfont = [];

    // PDF structure tracking
    protected $offsets = [];
    protected $n = 0;

    public function __construct($orientation='P', $unit='mm', $size='A4') {
        if (strtolower($orientation) === 'l' || strtolower($orientation) === 'landscape') {
            $this->fw = 297;
            $this->fh = 210;
        }
        $this->w = $this->fw;
        $this->h = $this->fh;
        $this->_docinit();
    }

    protected function _docinit() {
        $this->state = 0;
        $this->pages = [];
        $this->buffer = '';
        $this->offsets = [];
        $this->n = 0;
        $this->page = 0;
    }

    public function SetFont($family, $style='', $size=0) {
        $this->FontFamily = $family;
        $this->FontStyle = $style;
        if ($size > 0) $this->FontSizePt = $size;
    }

    public function AddPage($orientation='', $size='') {
        if ($this->state === 3) return;
        if ($this->state === 0) $this->_docinit();
        $this->page++;
        $this->pages[$this->page] = '';
        $this->state = 2;
        $this->x = $this->lMargin;
        $this->y = $this->tMargin;
        $this->PageBreakTrigger = $this->fh - $this->bMargin;
        $this->Header();
    }

    public function Cell($w, $h=0, $txt='', $border=0, $ln=0, $align='', $fill=false, $link='') {
        $k = $this->k;
        $txt = $this->_escape($txt);

        // Handle w=0: extend to right margin
        if ($w == 0) {
            $w = $this->w - $this->rMargin - $this->x;
        }

        $xP = $this->x * $k;
        $yP = ($this->h - $this->y) * $k;
        $wP = $w * $k;
        $hP = $h * $k;

        $ops = '';

        // Fill background
        if ($fill) {
            $ops .= 'q ' . $this->FillColor . ' ';
            $ops .= sprintf('%.2F %.2F %.2F %.2F re f Q ', $xP, $yP - $hP, $wP, $hP);
        }

        // Border
        if ($border) {
            $ops .= 'q ' . $this->DrawColor . ' ';
            $ops .= sprintf('%.2F %.2F %.2F %.2F re S Q ', $xP, $yP - $hP, $wP, $hP);
        }

        // Text alignment
        $textWidthPt = $this->GetStringWidth($txt) * $k; // convert mm to points
        if ($align === 'C') {
            $textX = $xP + ($wP - $textWidthPt) / 2;
        } elseif ($align === 'R') {
            $textX = $xP + $wP - $textWidthPt - 1;
        } else {
            $textX = $xP + 1;
        }

        // Vertical centering - baseline at ~35% from bottom of cell
        $textY = $yP - $hP + $hP * 0.35 + min(2, $this->FontSizePt * 0.25);

        $ops .= sprintf('BT %s /F%d %d Tf %.2F %.2F Td (%s) Tj ET',
            $this->TextColor, 1, $this->FontSizePt, $textX, $textY, $txt);

        $this->_out($ops);

        if ($ln) {
            $this->x = $this->lMargin;
            $this->y += $h;
        } else {
            $this->x += $w;
        }
    }

    public function MultiCell($w, $h, $txt, $border=0, $align='J', $fill=false) {
        $lines = explode("\n", $txt);
        foreach ($lines as $line) {
            $this->Cell($w, $h, $line, $border, 1, $align, $fill);
        }
    }

    public function Ln($h=null) {
        $this->x = $this->lMargin;
        if ($h === null) $this->y += $this->FontSizePt / $this->k * 1.2;
        else $this->y += $h;
    }

    public function SetX($x) { $this->x = $x; }
    public function GetX() { return $this->x; }
    public function GetY() { return $this->y; }
    public function GetPageWidth() { return $this->fw; }
    public function GetPageHeight() { return $this->fh; }
    public function GetLeftMargin() { return $this->lMargin; }
    public function SetY($y) {
        if ($y < 0) $this->y = $this->fh + $y;
        else $this->y = $y;
    }
    public function SetXY($x, $y) { $this->x = $x; $this->SetY($y); }

    public function SetMargins($left, $top, $right=null) {
        $this->lMargin = $left;
        $this->tMargin = $top;
        $this->rMargin = $right === null ? $left : $right;
    }

    public function SetAutoPageBreak($auto, $margin=0) {
        $this->AutoPageBreak = $auto;
        $this->bMargin = $margin;
        $this->PageBreakTrigger = $this->fh - $margin;
    }

    public function Header() {}
    public function Footer() {}

    public function SetTitle($title, $isUTF8=false) { $this->title = $title; }
    public function SetSubject($subject, $isUTF8=false) { $this->subject = $subject; }
    public function SetAuthor($author, $isUTF8=false) { $this->author = $author; }
    public function SetKeywords($keywords, $isUTF8=false) { $this->keywords = $keywords; }
    public function SetCreator($creator, $isUTF8=false) { $this->creator = $creator; }

    public function AliasNbPages($alias='{nb}') {
        $this->AliasNbPages = $alias;
    }

    public function PageNo() {
        return $this->page;
    }

    public function Output($dest='', $name='', $isUTF8=false) {
        if ($this->state < 3) $this->Close();
        if ($dest === 'D' || $dest === 'F') {
            if ($dest === 'D') {
                header('Content-Type: application/pdf');
                header('Content-Disposition: attachment; filename="'.$name.'"');
                header('Cache-Control: private, max-age=0, must-revalidate');
                header('Pragma: public');
                echo $this->buffer;
            }
            return $this->buffer;
        }
        if ($dest === 'S') return $this->buffer;
        if ($dest === 'I') {
            header('Content-Type: application/pdf');
            header('Content-Disposition: inline; filename="'.$name.'"');
            echo $this->buffer;
        }
        return '';
    }

    protected function Close() {
        if ($this->state === 3) return;
        $this->Footer();
        $this->state = 3;
        $this->_putheader();
        $this->_putpages();
        $this->_putresources();
        $this->_putinfo();
        $this->_puttrailer();
    }

    protected function _newobj($num) {
        $this->offsets[$num] = strlen($this->buffer);
        $this->_out($num . ' 0 obj');
    }

    protected function _putheader() {
        $this->_out('%PDF-' . $this->PDFVersion);
        $this->_out('%' . chr(226) . chr(227) . chr(207) . chr(211));

        // Object 1: Catalog
        $this->_newobj(1);
        $this->_out('<< /Type /Catalog /Pages 2 0 R >>');
        $this->_out('endobj');

        // Object 2: Pages list
        $this->_newobj(2);
        $kids = [];
        for ($i = 1; $i <= $this->page; $i++) {
            $kids[] = (2 * $i + 1) . ' 0 R';
        }
        $this->_out('<< /Type /Pages /Kids [' . implode(' ', $kids) . '] /Count ' . $this->page . ' >>');
        $this->_out('endobj');
    }

    protected function _putpages() {
        $pageW = sprintf('%.2F', $this->fw * $this->k);
        $pageH = sprintf('%.2F', $this->fh * $this->k);

        for ($i = 1; $i <= $this->page; $i++) {
            $pageObj = 2 * $i + 1;
            $contentObj = 2 * $i + 2;

            $content = str_replace($this->AliasNbPages, $this->page, $this->pages[$i]);

            // Page object
            $this->_newobj($pageObj);
            $this->_out('<< /Type /Page /Parent 2 0 R');
            $this->_out(' /MediaBox [0 0 ' . $pageW . ' ' . $pageH . ']');
            $this->_out(' /Resources << /Font << /F1 ' . (2 * $this->page + 3) . ' 0 R >> /ProcSet [/PDF /Text] >>');
            $this->_out(' /Contents ' . $contentObj . ' 0 R');
            $this->_out('>>');
            $this->_out('endobj');

            // Content stream
            $this->_newobj($contentObj);
            $this->_out('<< /Length ' . strlen($content) . ' >>');
            $this->_out('stream');
            $this->_out($content);
            $this->_out('endstream');
            $this->_out('endobj');
        }
    }

    protected function _putresources() {
        // Font object - always the last resource object
        $fontObj = 2 * $this->page + 3;
        $this->_newobj($fontObj);
        $this->_out('<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>');
        $this->_out('endobj');
    }

    protected function _putinfo() {
        $infoObj = 2 * $this->page + 4;
        $this->_newobj($infoObj);
        $this->_out('<<');
        if ($this->title) $this->_out(' /Title (' . $this->_escape($this->title) . ')');
        if ($this->subject) $this->_out(' /Subject (' . $this->_escape($this->subject) . ')');
        if ($this->author) $this->_out(' /Author (' . $this->_escape($this->author) . ')');
        $this->_out(' /Creator (' . $this->_escape($this->creator) . ')');
        $this->_out(' /Producer (SalesSphere PDF Generator)');
        $this->_out(' /CreationDate (D:' . date('YmdHis') . ')');
        $this->_out('>>');
        $this->_out('endobj');
    }

    protected function _puttrailer() {
        // Total objects = catalog(1) + pages(2) + N*(page+content) + font(1) + info(1) + xref free entry(0)
        $totalObjs = 2 * $this->page + 5;

        $startxref = strlen($this->buffer);
        $this->_out('xref');
        $this->_out('0 ' . $totalObjs);
        // Entry 0: free object
        $this->_out('0000000000 65535 f ');

        for ($i = 1; $i < $totalObjs; $i++) {
            if (isset($this->offsets[$i])) {
                $this->_out(sprintf('%010d 00000 n ', $this->offsets[$i]));
            } else {
                $this->_out('0000000000 00000 n ');
            }
        }

        $this->_out('trailer');
        $this->_out('<< /Size ' . $totalObjs . ' /Root 1 0 R /Info ' . (2 * $this->page + 4) . ' 0 R >>');
        $this->_out('startxref');
        $this->_out($startxref);
        $this->_out('%%EOF');
    }

    protected function _out($s) {
        if ($this->state === 2) {
            $this->pages[$this->page] .= $s . "\n";
        } else {
            $this->buffer .= $s . "\n";
        }
    }

    protected function _escape($s) {
        $s = str_replace(['\\', '(', ')', "\r"], ['\\\\', '\\(', '\\)', ''], $s);
        return $s;
    }

    public function SetFontSize($size) { $this->FontSizePt = $size; }

    public function GetStringWidth($s) {
        $l = strlen($s);
        return $l * $this->FontSizePt * 0.6 / $this->k;
    }

    public function SetTextColor($r, $g=-1, $b=-1) {
        if ($b === -1) { $g = $r; $b = $r; }
        $this->TextColor = sprintf('%.3F %.3F %.3F rg', $r/255, $g/255, $b/255);
    }

    public function SetDrawColor($r, $g=-1, $b=-1) {
        if ($b === -1) { $g = $r; $b = $r; }
        $this->DrawColor = sprintf('%.3F %.3F %.3F RG', $r/255, $g/255, $b/255);
    }

    public function SetFillColor($r, $g=-1, $b=-1) {
        if ($b === -1) { $g = $r; $b = $r; }
        $this->FillColor = sprintf('%.3F %.3F %.3F rg', $r/255, $g/255, $b/255);
    }

    public function SetLineWidth($w) {
        // Basic line width support
        $this->_out(sprintf('%.2F w', $w * $this->k));
    }

    public function Rect($x, $y, $w, $h, $style='') {
        $k = $this->k;
        $xP = $x * $k;
        $yP = ($this->h - $y) * $k;
        $wP = $w * $k;
        $hP = $h * $k;

        $draw = ($style !== 'F');
        $fill = ($style === 'F' || $style === 'DF' || $style === 'FD');

        $ops = '';
        if ($draw) $ops .= 'q ' . $this->DrawColor . ' ';
        if ($fill) $ops .= 'q ' . $this->FillColor . ' ';

        if ($draw && $fill) $op = 'B';
        elseif ($fill) $op = 'f';
        else $op = 'S';

        $ops .= sprintf('%.2F %.2F %.2F %.2F re %s', $xP, $yP, $wP, -$hP, $op);
        if ($draw) $ops .= ' Q';
        if ($fill) $ops .= ' Q';

        $this->_out(trim($ops));
    }

    public function Line($x1, $y1, $x2, $y2) {
        $k = $this->k;
        $this->_out(sprintf('q %s %.2F %.2F m %.2F %.2F l S Q',
            $this->DrawColor,
            $x1 * $k, ($this->h - $y1) * $k,
            $x2 * $k, ($this->h - $y2) * $k));
    }
}

/**
 * Extended PDF Report Class
 */
class PDFReport extends FPDF {
    protected $reportType;
    protected $reportTitle;
    protected $dateFrom;
    protected $dateTo;
    protected $customerType;
    protected $colWidths = [];
    protected $headers = [];
    protected $rowNum = 0;

    public function setReportConfig($type, $title, $from, $to, $customerType='all') {
        $this->reportType = $type;
        $this->reportTitle = $title;
        $this->dateFrom = $from;
        $this->dateTo = $to;
        $this->customerType = $customerType;
    }

    public function Header() {
        // Top branded bar - orange background
        $this->SetFillColor(255, 87, 34);
        $this->Rect(0, 0, $this->w, 12, 'F');

        $this->SetY(2);
        $this->SetFont('Helvetica', 'B', 11);
        $this->SetTextColor(255, 255, 255);
        $this->Cell(0, 5, 'SALESPHERE', 0, 1, 'C');
        $this->SetFont('Helvetica', '', 6);
        $this->Cell(0, 3, 'POS & Inventory Management System', 0, 1, 'C');

        // Report title
        $this->SetY(16);
        $this->SetFont('Helvetica', 'B', 14);
        $this->SetTextColor(24, 24, 27);
        $this->Cell(0, 7, $this->reportTitle, 0, 1, 'C');

        // Date range info
        $this->SetFont('Helvetica', '', 8);
        $this->SetTextColor(113, 113, 122);
        $from = date('d M Y', strtotime($this->dateFrom));
        $to = date('d M Y', strtotime($this->dateTo));
        $rangeText = "Period: {$from} to {$to}  |  Generated: " . date('d M Y H:i');
        if ($this->customerType !== 'all') {
            $rangeText .= "  |  Customer: " . ucfirst($this->customerType);
        }
        $this->Cell(0, 4, $rangeText, 0, 1, 'C');

        // Separator line
        $this->SetDrawColor(228, 228, 231);
        $this->Line(10, $this->GetY() + 1, $this->w - 10, $this->GetY() + 1);
        $this->Ln(4);

        $this->rowNum = 0;
    }

    public function Footer() {
        $this->SetY(-12);
        $this->SetFont('Helvetica', 'I', 7);
        $this->SetTextColor(161, 161, 170);
        $this->Cell(0, 8, 'Page ' . $this->PageNo() . ' / {nb}', 0, 0, 'C');
        $this->SetX(10);
        $this->Cell(0, 8, 'Generated: ' . date('d M Y H:i'), 0, 0, 'L');
        $this->SetX($this->w - 45);
        $this->Cell(0, 8, 'CONFIDENTIAL', 0, 0, 'R');
        // Bottom accent line
        $this->SetDrawColor(255, 87, 34);
        $this->Line(10, $this->GetY() + 9.5, $this->w - 10, $this->GetY() + 9.5);
    }

    public function TableHeader($headers, $widths) {
        $this->headers = $headers;
        $this->colWidths = $widths;

        $this->SetFillColor(39, 39, 42);
        $this->SetTextColor(255, 255, 255);
        $this->SetDrawColor(63, 63, 70);
        $this->SetFont('Helvetica', 'B', 7);

        for ($i = 0; $i < count($headers); $i++) {
            $this->Cell($widths[$i], 7, $headers[$i], 1, 0, 'C', true);
        }
        $this->Ln(7);
        $this->rowNum = 0;
    }

    protected function truncateText($text, $maxWidth) {
        $text = strval($text);
        if ($this->GetStringWidth($text) <= $maxWidth) return $text;
        $ellipsis = '...';
        $elW = $this->GetStringWidth($ellipsis);
        while ($text !== '' && $this->GetStringWidth($text) + $elW > $maxWidth) {
            $text = mb_substr($text, 0, mb_strlen($text) - 1);
        }
        return $text . $ellipsis;
    }

    public function TableRow($row, $alignments=null) {
        $rowHeight = 6;

        // Auto page break
        if ($this->GetY() + $rowHeight > $this->PageBreakTrigger) {
            $this->AddPage();
            $this->TableHeader($this->headers, $this->colWidths);
        }

        $this->rowNum++;

        // Alternating row background
        if ($this->rowNum % 2 === 0) {
            $this->SetFillColor(250, 250, 250);
        } else {
            $this->SetFillColor(255, 255, 255);
        }
        $this->SetTextColor(24, 24, 27);
        $this->SetDrawColor(228, 228, 231);
        $this->SetFont('Helvetica', '', 6.5);

        $xStart = $this->GetX();
        $yStart = $this->GetY();

        for ($i = 0; $i < count($this->headers); $i++) {
            $x = $xStart + array_sum(array_slice($this->colWidths, 0, $i));
            $w = $this->colWidths[$i];
            $text = strval($row[$i] ?? '');
            $align = $alignments ? ($alignments[$i] ?? 'L') : 'L';
            $text = $this->truncateText($text, $w - 3);

            // Cell background + border
            $this->Rect($x, $yStart, $w, $rowHeight, 'DF');
            // Cell text
            $this->SetXY($x + 1, $yStart + 1);
            $this->Cell($w - 2, $rowHeight - 2, $text, 0, 0, $align);
        }

        $this->SetXY($xStart, $yStart + $rowHeight);
    }

    public function SummaryRow($labels, $values, $alignments=null) {
        $rowHeight = 10;
        $count = count($labels);
        if ($count === 0) return;

        if ($this->GetY() + $rowHeight + 4 > $this->PageBreakTrigger) {
            $this->AddPage();
        }

        // Top separator line across full page width
        $y = $this->GetY();
        $this->SetDrawColor(255, 87, 34);
        $this->Line($this->lMargin, $y, $this->w - $this->rMargin, $y);
        $this->Ln(4);

        // Distribute summary items evenly across the full table width
        $totalWidth = array_sum($this->colWidths);
        $cellWidth = $totalWidth / $count;

        $this->SetFillColor(255, 245, 238);
        $this->SetTextColor(24, 24, 27);
        $this->SetDrawColor(228, 228, 231);
        $this->SetFont('Helvetica', 'B', 8);

        $xStart = $this->lMargin;
        $yStart = $this->GetY();

        for ($i = 0; $i < $count; $i++) {
            $x = $xStart + $cellWidth * $i;
            $w = $cellWidth - 0.5;
            $align = $alignments ? ($alignments[$i] ?? 'L') : 'L';
            $text = $labels[$i] . ': ' . $values[$i];

            $this->Rect($x, $yStart, $w, $rowHeight, 'DF');
            $this->SetXY($x + 1.5, $yStart + 1.5);
            $this->Cell($w - 3, $rowHeight - 3, $text, 0, 0, $align);
        }

        $this->SetXY($this->lMargin, $yStart + $rowHeight);
    }
}

/**
 * Generate Sales Report PDF
 */
function generateSalesReportPDF($data, $from, $to, $customerType) {
    $pdf = new PDFReport('L');
    $pdf->AliasNbPages();
    $pdf->setReportConfig('sales', 'Sales Report', $from, $to, $customerType);
    $pdf->SetMargins(8, 8, 8);
    $pdf->AddPage();

    $headers = ['Date', 'Order #', 'Source', 'Branch', 'Customer', 'Items', 'Subtotal (Rs)', 'Tax (Rs)', 'Total (Rs)', 'Status'];
    $widths = [25, 28, 22, 40, 43, 12, 31, 25, 34, 22];
    $pdf->TableHeader($headers, $widths);

    $alignments = ['C', 'C', 'C', 'L', 'L', 'C', 'R', 'R', 'R', 'C'];
    $totals = ['subtotal' => 0, 'tax' => 0, 'total' => 0, 'count' => 0];

    foreach ($data as $r) {
        $pdf->TableRow([
            date('d/m/Y', strtotime($r['CreatedAt'])),
            $r['OrderID'] ?? $r['OnlineOrderID'] ?? '',
            $r['Source'] ?? 'In-store',
            $r['BranchName'] ?? '-',
            $r['CustomerName'] ?? 'Walk-in',
            (int)($r['ItemCount'] ?? 0),
            'Rs ' . number_format((float)($r['Subtotal'] ?? 0), 2),
            'Rs ' . number_format((float)($r['TaxAmount'] ?? 0), 2),
            'Rs ' . number_format((float)($r['Total'] ?? 0), 2),
            $r['Status'] ?? ''
        ], $alignments);

        $totals['subtotal'] += (float)($r['Subtotal'] ?? 0);
        $totals['tax'] += (float)($r['TaxAmount'] ?? 0);
        $totals['total'] += (float)($r['Total'] ?? 0);
        $totals['count']++;
    }

    $pdf->Ln(3);
    $pdf->SummaryRow(
        ['Total Orders', 'Total Subtotal', 'Total Tax', 'Grand Total'],
        [$totals['count'], 'Rs ' . number_format($totals['subtotal'], 2), 'Rs ' . number_format($totals['tax'], 2), 'Rs ' . number_format($totals['total'], 2)],
        ['L', 'R', 'R', 'R']
    );

    $filename = 'sales_report_' . date('Ymd', strtotime($from)) . '_to_' . date('Ymd', strtotime($to)) . '.pdf';
    return $pdf->Output('S', $filename);
}

/**
 * Generate Revenue by Branch Report PDF
 */
function generateRevenueReportPDF($data, $from, $to) {
    $pdf = new PDFReport('L');
    $pdf->AliasNbPages();
    $pdf->setReportConfig('revenue', 'Revenue by Branch', $from, $to);
    $pdf->SetMargins(8, 8, 8);
    $pdf->AddPage();

    $headers = ['Branch', 'Orders', 'Subtotal (Rs)', 'Tax (Rs)', 'Total (Rs)', 'Refunds (Rs)', 'Net Revenue (Rs)'];
    $widths = [54, 25, 44, 34, 44, 34, 47];
    $pdf->TableHeader($headers, $widths);

    $alignments = ['L', 'C', 'R', 'R', 'R', 'R', 'R'];
    $totals = ['orders' => 0, 'subtotal' => 0, 'tax' => 0, 'total' => 0, 'refunds' => 0, 'net' => 0];

    foreach ($data as $r) {
        $net = (float)($r['Total'] ?? 0) - (float)($r['RefundTotal'] ?? 0);
        $pdf->TableRow([
            $r['BranchName'] ?? '-',
            (int)($r['OrderCount'] ?? 0),
            'Rs ' . number_format((float)($r['Subtotal'] ?? 0), 2),
            'Rs ' . number_format((float)($r['TaxAmount'] ?? 0), 2),
            'Rs ' . number_format((float)($r['Total'] ?? 0), 2),
            'Rs ' . number_format((float)($r['RefundTotal'] ?? 0), 2),
            'Rs ' . number_format($net, 2)
        ], $alignments);

        $totals['orders'] += (int)($r['OrderCount'] ?? 0);
        $totals['subtotal'] += (float)($r['Subtotal'] ?? 0);
        $totals['tax'] += (float)($r['TaxAmount'] ?? 0);
        $totals['total'] += (float)($r['Total'] ?? 0);
        $totals['refunds'] += (float)($r['RefundTotal'] ?? 0);
        $totals['net'] += $net;
    }

    $pdf->Ln(3);
    $pdf->SummaryRow(
        ['Total Branches', 'Total Orders', 'Total Subtotal', 'Total Tax', 'Grand Total', 'Total Refunds', 'Net Revenue'],
        [count($data), $totals['orders'], 'Rs ' . number_format($totals['subtotal'], 2), 'Rs ' . number_format($totals['tax'], 2), 'Rs ' . number_format($totals['total'], 2), 'Rs ' . number_format($totals['refunds'], 2), 'Rs ' . number_format($totals['net'], 2)],
        ['L', 'C', 'R', 'R', 'R', 'R', 'R']
    );

    $filename = 'revenue_by_branch_' . date('Ymd', strtotime($from)) . '_to_' . date('Ymd', strtotime($to)) . '.pdf';
    return $pdf->Output('S', $filename);
}

/**
 * Generate Revenue Tracking Report PDF (daily or monthly view)
 */
function generateRevenueTrackingPDF($data, $from, $to, $view, $totalGross, $totalRefunds, $totalNet) {
    $pdf = new PDFReport('L');
    $pdf->AliasNbPages();
    $title = $view === 'daily' ? 'Daily Revenue Tracking' : 'Monthly Revenue Tracking';
    $pdf->setReportConfig('revenuetracking', $title, $from, $to);
    $pdf->SetMargins(8, 8, 8);
    $pdf->AddPage();

    $headers = [$view === 'daily' ? 'Date' : 'Month', 'Orders', 'Gross Revenue (Rs)', 'Tax (Rs)', 'Refunds (Rs)', 'Net Revenue (Rs)'];
    $widths = [52, 30, 60, 48, 40, 50];
    $pdf->TableHeader($headers, $widths);
    $alignments = ['C', 'C', 'R', 'R', 'R', 'R'];

    foreach ($data as $r) {
        $pdf->TableRow([
            $r['Date'] ?? $r['Month'] ?? '-',
            (int)($r['OrderCount'] ?? 0),
            'Rs ' . number_format((float)($r['GrossRevenue'] ?? 0), 2),
            'Rs ' . number_format((float)($r['TaxAmount'] ?? 0), 2),
            'Rs ' . number_format((float)($r['RefundAmount'] ?? 0), 2),
            'Rs ' . number_format((float)($r['NetRevenue'] ?? 0), 2),
        ], $alignments);
    }

    $pdf->Ln(3);
    $pdf->SummaryRow(
        ['Period Totals', 'Orders', 'Gross Revenue', 'Tax', 'Refunds', 'Net Revenue'],
        [
            '',
            array_sum(array_column($data, 'OrderCount')),
            'Rs ' . number_format($totalGross, 2),
            'Rs ' . number_format(array_sum(array_column($data, 'TaxAmount')), 2),
            'Rs ' . number_format($totalRefunds, 2),
            'Rs ' . number_format($totalNet, 2),
        ],
        ['C', 'C', 'R', 'R', 'R', 'R']
    );

    $filename = 'revenue_tracking_' . date('Ymd', strtotime($from)) . '_to_' . date('Ymd', strtotime($to)) . '.pdf';
    return $pdf->Output('S', $filename);
}

/**
 * Generate Inventory Report PDF
 */
function generateInventoryReportPDF($data) {
    $pdf = new PDFReport('L');
    $pdf->AliasNbPages();
    $pdf->setReportConfig('inventory', 'Inventory Report', date('Y-m-d'), date('Y-m-d'));
    $pdf->SetMargins(8, 8, 8);
    $pdf->AddPage();

    $headers = ['Product', 'SKU / Barcode', 'Branch', 'Qty', 'Reorder Level', 'Min Stock', 'Status'];
    $widths = [73, 48, 42, 24, 33, 30, 30];
    $pdf->TableHeader($headers, $widths);

    $alignments = ['L', 'L', 'L', 'C', 'C', 'C', 'C'];
    $lowStock = 0;
    $totalItems = 0;

    foreach ($data as $r) {
        $qty = (int)($r['AvailableQty'] ?? 0);
        $reorder = (int)($r['ReorderLevel'] ?? 0);
        $isLow = $qty <= $reorder;
        if ($isLow) $lowStock++;
        $totalItems++;
        $status = $isLow ? 'LOW STOCK' : 'In Stock';

        $pdf->TableRow([
            $r['ProductName'] ?? '-',
            ($r['Sku'] ?? $r['Barcode'] ?? '-'),
            $r['BranchName'] ?? '-',
            $qty,
            $reorder,
            (int)($r['MinStockLevel'] ?? 0),
            $status
        ], $alignments);
    }

    $pdf->Ln(3);
    $pdf->SummaryRow(
        ['Total Products', 'Low Stock Items', 'In Stock'],
        [$totalItems, $lowStock, $totalItems - $lowStock],
        ['L', 'R', 'R']
    );

    $filename = 'inventory_report_' . date('Ymd') . '.pdf';
    return $pdf->Output('S', $filename);
}

/**
 * Generate Refund Report PDF
 */
function generateRefundReportPDF($data, $from, $to) {
    $pdf = new PDFReport('L');
    $pdf->AliasNbPages();
    $pdf->setReportConfig('refunds', 'Refund / Return Report', $from, $to);
    $pdf->SetMargins(8, 8, 8);
    $pdf->AddPage();

    $headers = ['Date', 'Return #', 'Order #', 'Branch', 'Reason', 'Refund (Rs)', 'Status'];
    $widths = [28, 35, 35, 44, 69, 35, 35];
    $pdf->TableHeader($headers, $widths);

    $alignments = ['C', 'C', 'C', 'L', 'L', 'R', 'C'];
    $totalRefunds = 0;
    $count = 0;

    foreach ($data as $r) {
        $pdf->TableRow([
            date('d/m/Y', strtotime($r['CreatedAt'])),
            $r['ReturnID'] ?? '',
            $r['OrderID'] ?? (isset($r['OnlineOrderID']) ? 'ONLINE#' . $r['OnlineOrderID'] : '-'),
            $r['BranchName'] ?? '-',
            $r['Reason'] ?? '-',
            'Rs ' . number_format((float)($r['RefundAmount'] ?? 0), 2),
            $r['Status'] ?? ''
        ], $alignments);

        $totalRefunds += (float)($r['RefundAmount'] ?? 0);
        $count++;
    }

    $pdf->Ln(3);
    $pdf->SummaryRow(
        ['Total Returns', 'Total Refund Amount'],
        [$count, 'Rs ' . number_format($totalRefunds, 2)],
        ['L', 'R']
    );

    $filename = 'refund_report_' . date('Ymd', strtotime($from)) . '_to_' . date('Ymd', strtotime($to)) . '.pdf';
    return $pdf->Output('S', $filename);
}

/**
 * Generate Invoice PDF for a given bill ID
 */
function generateInvoicePDF($id) {
    $b = \App\Models\BillManager::getById($id);
    if (!$b) return false;
    $items = \App\Models\BillManager::getItems($id);
    $payments = \App\Models\BillManager::getPayments($id);

    $pdf = new FPDF('P', 'mm', 'A4');
    $pdf->SetMargins(15, 15, 15);
    $pdf->AddPage();

    $pw = $pdf->GetPageWidth() - 30;

    // === Header bar ===
    $pdf->SetFillColor(255, 87, 34);
    $pdf->Rect(0, 0, 210, 35, 'F');
    $pdf->SetY(10);
    $pdf->SetFont('', 'B', 16);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->Cell(0, 7, 'SALESPHERE', 0, 1, 'C');
    $pdf->SetFont('', '', 8);
    $pdf->Cell(0, 4, 'POS & Inventory Management System', 0, 1, 'C');

    // === Invoice title and number ===
    $pdf->SetY(42);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFont('', 'B', 18);
    $pdf->Cell(0, 8, $b['BillType'] ?? 'Invoice', 0, 1, 'C');
    $pdf->SetFont('', '', 10);
    $pdf->SetTextColor(100, 100, 100);
    $pdf->Cell(0, 5, $b['BillNumber'] ?? '', 0, 1, 'C');
    $pdf->Ln(4);

    // === Business info (left) and Invoice info (right) ===
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFont('', 'B', 9);
    $pdf->Cell($pw * 0.5, 5, 'Business Details', 0, 0, 'L');
    $pdf->Cell($pw * 0.5, 5, 'Invoice Details', 0, 1, 'R');
    $pdf->SetFont('', '', 8.5);
    $pdf->SetTextColor(60, 60, 60);

    $leye = $pdf->GetY();
    $pdf->MultiCell($pw * 0.5, 4, "Salesphere\n123 Main Street, Colombo\ninfo@salesphere.com | +94 11-2345678", 0, 'L');
    $ry = $pdf->GetY();
    $pdf->SetXY(15 + $pw * 0.5, $leye);
    $rightInfo = "Date: " . date('d/m/Y', strtotime($b['CreatedAt']));
    if ($b['DueDate']) $rightInfo .= "\nDue: " . date('d/m/Y', strtotime($b['DueDate']));
    $rightInfo .= "\nBranch: " . ($b['BranchName'] ?? '-');
    if ($b['ReferenceType'] !== 'Manual' && $b['ReferenceID']) {
        $rightInfo .= "\nRef: #" . $b['ReferenceID'];
    }
    $pdf->MultiCell($pw * 0.5, 4, $rightInfo, 0, 'R');
    $pdf->SetY(max($pdf->GetY(), $ry) + 2);

    // === Customer & cashier info ===
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFont('', 'B', 9);
    $pdf->Cell($pw * 0.5, 5, 'Bill To', 0, 0, 'L');
    $pdf->Cell($pw * 0.5, 5, 'Processed By', 0, 1, 'R');
    $pdf->SetFont('', '', 8.5);
    $pdf->SetTextColor(60, 60, 60);
    $pdf->Cell($pw * 0.5, 4, $b['CustomerName'] ?? 'Walk-in Customer', 0, 0, 'L');
    $pdf->Cell($pw * 0.5, 4, $b['CashierName'] ?? '-', 0, 1, 'R');
    $pdf->Ln(6);

    // === Items table ===
    $cols = [8, 80, 20, 28, 24, 28];
    $header = ['#', 'Item / Description', 'Qty', 'Unit Price', 'Tax', 'Total'];
    $pdf->SetFillColor(39, 39, 42);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->SetFont('', 'B', 7.5);
    $pdf->Cell($cols[0], 7, $header[0], 1, 0, 'C', true);
    $pdf->Cell($cols[1], 7, $header[1], 1, 0, 'L', true);
    $pdf->Cell($cols[2], 7, $header[2], 1, 0, 'C', true);
    $pdf->Cell($cols[3], 7, $header[3], 1, 0, 'R', true);
    $pdf->Cell($cols[4], 7, $header[4], 1, 0, 'R', true);
    $pdf->Cell($cols[5], 7, $header[5], 1, 0, 'R', true);
    $pdf->Ln();

    $pdf->SetTextColor(0, 0, 0);
    $rowNum = 0;
    foreach ($items as $item) {
        $rowNum++;
        $fill = ($rowNum % 2 === 0);
        $pdf->SetFillColor(250, 250, 250);
        if (!$fill) $pdf->SetFillColor(255, 255, 255);
        $pdf->SetFont('', '', 7.5);
        $pdf->Cell($cols[0], 6, (string)$rowNum, 1, 0, 'C', true);
        $pdf->Cell($cols[1], 6, $item['Description'], 1, 0, 'L', true);
        $pdf->Cell($cols[2], 6, number_format((float)$item['Quantity'], 2), 1, 0, 'C', true);
        $pdf->Cell($cols[3], 6, 'Rs ' . number_format((float)$item['UnitPrice'], 2), 1, 0, 'R', true);
        $pdf->Cell($cols[4], 6, 'Rs ' . number_format((float)$item['TaxAmount'], 2), 1, 0, 'R', true);
        $pdf->Cell($cols[5], 6, 'Rs ' . number_format((float)$item['LineTotal'], 2), 1, 0, 'R', true);
        $pdf->Ln();
    }

    // === Totals section ===
    $pdf->Ln(2);
    $balance = (float)$b['Total'] - (float)$b['AmountPaid'];
    $totalsData = [
        ['Subtotal', (float)$b['Subtotal'], false],
        ['Tax', (float)$b['TaxAmount'], false],
    ];
    if ((float)$b['DiscountAmount'] > 0) {
        $totalsData[] = ['Discount', -(float)$b['DiscountAmount'], false];
    }
    $totalsData[] = ['Total', (float)$b['Total'], true];
    $totalsData[] = ['Paid', -(float)$b['AmountPaid'], false];
    if ($balance > 0.001) {
        $totalsData[] = ['Balance Due', $balance, true];
    }

    $tw = 70;
    $tx = 210 - 15 - $tw;
    foreach ($totalsData as $td) {
        $pdf->SetX($tx);
        $pdf->SetFont('', $td[2] ? 'B' : '', 8);
        $val = $td[1] < 0 ? 'Rs (' . number_format(abs($td[1]), 2) . ')' : 'Rs ' . number_format($td[1], 2);
        if ($td[0] === 'Paid') {
            $pdf->SetTextColor(22, 163, 74);
        } elseif ($td[0] === 'Balance Due') {
            $pdf->SetTextColor(220, 38, 38);
        } else {
            $pdf->SetTextColor(0, 0, 0);
        }
        $pdf->Cell($tw * 0.55, 5, $td[0], 0, 0, 'L');
        $pdf->Cell($tw * 0.45, 5, $val, 0, 1, 'R');
    }

    // === Payment history ===
    if (!empty($payments)) {
        $pdf->Ln(4);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetFont('', 'B', 9);
        $pdf->Cell(0, 5, 'Payment History', 0, 1, 'L');
        $pdf->SetFont('', '', 7.5);
        $pCols = [35, 30, 50, 30];
        $pdf->SetFillColor(39, 39, 42);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->Cell($pCols[0], 6, 'Method', 1, 0, 'L', true);
        $pdf->Cell($pCols[1], 6, 'Amount', 1, 0, 'R', true);
        $pdf->Cell($pCols[2], 6, 'Reference', 1, 0, 'L', true);
        $pdf->Cell($pCols[3], 6, 'Date', 1, 0, 'L', true);
        $pdf->Ln();
        $pdf->SetTextColor(0, 0, 0);
        foreach ($payments as $pmt) {
            $pdf->Cell($pCols[0], 5, $pmt['PaymentMethod'], 1, 0, 'L');
            $pdf->Cell($pCols[1], 5, 'Rs ' . number_format((float)$pmt['Amount'], 2), 1, 0, 'R');
            $pdf->Cell($pCols[2], 5, $pmt['Reference'] ?? '-', 1, 0, 'L');
            $pdf->Cell($pCols[3], 5, date('d/m/Y H:i', strtotime($pmt['ReceivedAt'])), 1, 0, 'L');
            $pdf->Ln();
        }
    }

    // === Notes ===
    if (!empty($b['Notes'])) {
        $pdf->Ln(4);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetFont('', 'B', 9);
        $pdf->Cell(0, 5, 'Notes', 0, 1, 'L');
        $pdf->SetFont('', '', 8);
        $pdf->SetTextColor(80, 80, 80);
        $pdf->MultiCell(0, 4, $b['Notes'], 0, 'L');
    }

    // === Footer ===
    $pdf->SetY(-40);
    $pdf->SetDrawColor(255, 87, 34);
    $pdf->Line(15, $pdf->GetY(), 195, $pdf->GetY());
    $pdf->Ln(3);
    $pdf->SetTextColor(120, 120, 120);
    $pdf->SetFont('', '', 7.5);
    $pdf->Cell(0, 4, 'Thank you for your business!', 0, 1, 'C');
    $pdf->Cell(0, 4, 'Salesphere Management System | Generated on ' . date('d/m/Y H:i'), 0, 1, 'C');

    $filename = 'invoice_' . str_replace('/', '-', $b['BillNumber'] ?? $id) . '.pdf';
    return $pdf->Output('S', $filename);
}

/**
 * Generate Shift Report PDF
 */
function generateShiftReportPDF($orders, $from, $to, $totalSales, $totalOrders, $totalItems, $totalTax, $totalPaid, $totalChange, $methodBreakdown, $refunds=[], $totalRefunds=0) {
    $pdf = new PDFReport('L');
    $pdf->AliasNbPages();
    $pdf->setReportConfig('shiftreport', 'Shift Sales Report', $from, $to);
    $pdf->SetMargins(8, 8, 8);
    $pdf->AddPage();

    // === Summary Cards ===
    $netSales = $totalSales - $totalRefunds;
    $pw = $pdf->GetPageWidth() - 16;
    // 7 or 8 cards depending on refunds
    $cardCount = $totalRefunds > 0 ? 8 : 6;
    $cellW = $pw / $cardCount;
    $summaryItems = [
        ['Total Orders', $totalOrders],
        ['Items Sold', $totalItems],
        ['Gross Sales', 'Rs ' . number_format($totalSales, 2)],
        ['Total Tax', 'Rs ' . number_format($totalTax, 2)],
        ['Total Paid', 'Rs ' . number_format($totalPaid, 2)],
        ['Change Given', 'Rs ' . number_format($totalChange, 2)],
    ];
    if ($totalRefunds > 0) {
        $summaryItems[] = ['Total Refunds', '(Rs ' . number_format($totalRefunds, 2) . ')'];
        $summaryItems[] = ['Net Sales', 'Rs ' . number_format($netSales, 2)];
    }

    $y = $pdf->GetY();
    $pdf->SetFillColor(255, 245, 238);
    $pdf->SetDrawColor(228, 228, 231);
    foreach ($summaryItems as $i => $item) {
        $x = 8 + $cellW * $i;
        $isRefund = $item[0] === 'Total Refunds';
        $isNet = $item[0] === 'Net Sales';
        $r = $isNet ? 34 : ($isRefund ? 239 : 113);
        $g = $isNet ? 197 : ($isRefund ? 68 : 113);
        $b = $isNet ? 94 : ($isRefund ? 68 : 122);
        $pdf->Rect($x, $y, $cellW - 1, 14, 'DF');
        $pdf->SetXY($x + 1, $y + 1);
        $pdf->SetFont('Helvetica', '', 6);
        $pdf->SetTextColor($r, $g, $b);
        $pdf->Cell($cellW - 2, 4, $item[0], 0, 0, 'C');
        $pdf->SetXY($x + 1, $y + 6);
        $pdf->SetFont('Helvetica', 'B', 9);
        $pdf->SetTextColor($isNet ? 34 : ($isRefund ? 239 : 24), $isNet ? 197 : ($isRefund ? 68 : 24), $isNet ? 94 : ($isRefund ? 68 : 27));
        $pdf->Cell($cellW - 2, 6, (string)$item[1], 0, 0, 'C');
    }
    $pdf->SetXY(8, $y + 18);

    // === Payment Method Breakdown ===
    if (!empty($methodBreakdown)) {
        $pdf->Ln(2);
        $pdf->SetFont('Helvetica', 'B', 9);
        $pdf->SetTextColor(24, 24, 27);
        $pdf->Cell(0, 6, 'Payment Method Breakdown', 0, 1, 'L');
        $pdf->Ln(4);

        $availW = $pdf->GetPageWidth() - 16;
        $mWidths = [round($availW * 0.40), round($availW * 0.35), round($availW * 0.25)];
        $pmHeaders = ['Method', 'Total', '% of Sales'];
        $pmAligns = ['L', 'R', 'R'];

        // Header row — Rect + Cell pattern (same as TableHeader)
        $xStart = $pdf->GetX();
        $yStart = $pdf->GetY();
        $pdf->SetFillColor(39, 39, 42);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetDrawColor(63, 63, 70);
        $pdf->SetFont('Helvetica', 'B', 7);
        for ($i = 0; $i < 3; $i++) {
            $x = $xStart + ($i > 0 ? array_sum(array_slice($mWidths, 0, $i)) : 0);
            $pdf->Rect($x, $yStart, $mWidths[$i], 8, 'DF');
            $pdf->SetXY($x + 1.5, $yStart + 1.5);
            $pdf->Cell($mWidths[$i] - 3, 5, $pmHeaders[$i], 0, 0, $pmAligns[$i]);
        }
        $pdf->SetXY($xStart, $yStart + 8);

        // Data rows — white text on dark background, alternating shades
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetDrawColor(63, 63, 70);
        $pdf->SetFont('Helvetica', '', 7);
        $rowNum = 0;
        foreach ($methodBreakdown as $method => $amount) {
            $pct = $totalSales > 0 ? round($amount / $totalSales * 100, 1) : 0;
            $fill = $rowNum % 2 === 0 ? [39, 39, 42] : [55, 55, 58];
            $pdf->SetFillColor($fill[0], $fill[1], $fill[2]);
            $rowData = [$method, 'Rs ' . number_format($amount, 2), $pct . '%'];
            $yStart = $pdf->GetY();
            for ($i = 0; $i < 3; $i++) {
                $x = $xStart + ($i > 0 ? array_sum(array_slice($mWidths, 0, $i)) : 0);
                $pdf->Rect($x, $yStart, $mWidths[$i], 7, 'DF');
                $pdf->SetXY($x + 1.5, $yStart + 1.5);
                $pdf->Cell($mWidths[$i] - 3, 4, $rowData[$i], 0, 0, $pmAligns[$i]);
            }
            $pdf->SetXY($xStart, $yStart + 7);
            $rowNum++;
        }
        $pdf->Ln(5);
    }

    // === Orders Table with Subtotal & Tax columns ===
    $headers = ['Order #', 'Date', 'Cashier', 'Customer', 'Items', 'Method', 'Subtotal (Rs)', 'Tax (Rs)', 'Total (Rs)', 'Paid (Rs)'];
    $widths = [18, 24, 22, 46, 12, 22, 30, 24, 30, 30];
    $pdf->TableHeader($headers, $widths);
    $alignments = ['C', 'C', 'L', 'L', 'C', 'C', 'R', 'R', 'R', 'R'];

    foreach ($orders as $o) {
        $itemCount = isset($o['_itemCount']) ? (int)$o['_itemCount'] : 0;
        $subtotal = (float)$o['Total'] - (float)$o['TaxAmount'];
        $pdf->TableRow([
            $o['OrderID'],
            date('d/m/Y H:i', strtotime($o['CreatedAt'])),
            $o['CashierName'] ?? '-',
            $o['CustomerName'] ?? 'Walk-in',
            (int)$itemCount,
            $o['PaymentMethod'] ?? 'Cash',
            'Rs ' . number_format($subtotal, 2),
            'Rs ' . number_format((float)$o['TaxAmount'], 2),
            'Rs ' . number_format((float)$o['Total'], 2),
            'Rs ' . number_format((float)$o['AmountPaid'], 2),
        ], $alignments);
    }

    $pdf->Ln(3);
    $summaryLabels = ['Total Orders', 'Items Sold', 'Gross Sales', 'Total Tax', 'Total Paid', 'Change Given'];
    $summaryValues = [
        $totalOrders,
        $totalItems,
        'Rs ' . number_format($totalSales, 2),
        'Rs ' . number_format($totalTax, 2),
        'Rs ' . number_format($totalPaid, 2),
        'Rs ' . number_format($totalChange, 2)
    ];
    $alignments = ['C', 'C', 'R', 'R', 'R', 'R'];
    if ($totalRefunds > 0) {
        $summaryLabels[] = 'Total Refunds';
        $summaryValues[] = '(Rs ' . number_format($totalRefunds, 2) . ')';
        $summaryLabels[] = 'Net Sales';
        $summaryValues[] = 'Rs ' . number_format($netSales, 2);
        $alignments[] = 'R';
        $alignments[] = 'R';
    }
    $pdf->SummaryRow($summaryLabels, $summaryValues, $alignments);

    // === Refunds Table (if any) ===
    if (!empty($refunds)) {
        $pdf->Ln(4);
        $pdf->SetFont('Helvetica', 'B', 9);
        $pdf->SetTextColor(24, 24, 27);
        $pdf->Cell(0, 6, 'Refunds / Returns', 0, 1, 'L');
        $pdf->Ln(3);

        $rHeaders = ['Return #', 'Date', 'Branch', 'Reason', 'Refund (Rs)', 'Status'];
        $rWidths = [24, 32, 48, 82, 36, 30];
        $pdf->TableHeader($rHeaders, $rWidths);
        $rAlignments = ['C', 'C', 'L', 'L', 'R', 'C'];

        foreach ($refunds as $r) {
            $pdf->TableRow([
                $r['ReturnID'],
                date('d/m/Y H:i', strtotime($r['RequestedAt'] ?? $r['CreatedAt'])),
                $r['BranchName'] ?? '-',
                $r['Reason'] ?? '-',
                '(Rs ' . number_format((float)($r['TotalRefund'] ?? 0), 2) . ')',
                $r['Status'] ?? '-',
            ], $rAlignments);
        }

        $pdf->Ln(3);
        $pdf->SummaryRow(
            ['Total Refunds'],
            ['(Rs ' . number_format($totalRefunds, 2) . ')'],
            ['R']
        );
    }

    $filename = 'shift_report_' . date('Ymd', strtotime($from)) . '_to_' . date('Ymd', strtotime($to)) . '.pdf';
    return $pdf->Output('S', $filename);
}
