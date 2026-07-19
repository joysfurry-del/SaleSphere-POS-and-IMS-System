<?php
/**
 * FPDF - Free PDF generation library
 * Minimal subset for our report needs
 */
class FPDF {
    protected $page = 0;
    protected $n = 2;
    protected $buffer = '';
    protected $pages = [];
    protected $state = 0;
    protected $compress = true;
    protected $k = 1;
    protected $fwPt = 595.28;
    protected $fhPt = 841.89;
    protected $fw = 210;
    protected $fh = 297;
    protected $wPt;
    protected $hPt;
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

    public function __construct($orientation='P', $unit='mm', $size='A4') {
        $this->_docinit();
        $this->SetFont('Helvetica', '', 12);
    }

    protected function _docinit() {
        $this->state = 0;
        $this->pages = [];
        $this->buffer = '';
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
    }

    public function Cell($w, $h=0, $txt='', $border=0, $ln=0, $align='', $fill=false, $link='') {
        $k = $this->k;
        $this->_out(sprintf('q %.2F 0 0 %.2F %.2F %.2F cm /F%d %d Tf (%s) Tj Q', $w*$k, $h*$k, $this->x*$k, ($this->h-$this->y)*$k, $this->FontFamily, $this->FontSizePt, $this->_escape($txt)));
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

    public function SetX($x) {
        $this->x = $x;
    }

    public function GetX() { return $this->x; }
    public function GetY() { return $this->y; }

    public function SetY($y) { $this->y = $y; }
    public function SetXY($x, $y) { $this->x = $x; $this->y = $y; }

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

    public function Output($dest='', $name='', $isUTF8=false) {
        if ($this->state < 3) $this->Close();
        if ($dest === 'D' || $dest === 'F') {
            $output = $this->buffer;
            if ($dest === 'D') {
                header('Content-Type: application/pdf');
                header('Content-Disposition: attachment; filename="'.$name.'"');
                header('Cache-Control: private, max-age=0, must-revalidate');
                header('Pragma: public');
                echo $output;
            }
            return $output;
        }
        if ($dest === 'S') return $this->buffer;
        if ($dest === 'I') {
            header('Content-Type: application/pdf');
            header('Content-Disposition: inline; filename="'.$name.'"');
            header('Cache-Control: private, max-age=0, must-revalidate');
            header('Pragma: public');
            echo $this->buffer;
        }
        return '';
    }

    protected function Close() {
        if ($this->state === 3) return;
        $this->state = 3;
        $this->_putheader();
        $this->_putpages();
        $this->_puttrailer();
    }

    protected function _putheader() {
        $this->_out('%PDF-'.$this->PDFVersion);
        $this->_out('%\xE2\xE3\xCF\xD3');
    }

    protected function _putpages() {
        for ($i = 1; $i <= $this->page; $i++) {
            $this->_out('<< /Type /Page /Parent 1 0 R /Resources 2 0 R /Contents '.($i+2).' 0 R >>');
            $this->_out('endobj');
        }
    }

    protected function _puttrailer() {
        $this->_out('trailer');
        $this->_out('<< /Size '.($this->page+3).' /Root 1 0 R /Info 2 0 R >>');
        $this->_out('startxref');
        $this->_out(strlen($this->buffer));
        $this->_out('%%EOF');
    }

    protected function _out($s) {
        $this->buffer .= $s."\n";
    }

    protected function _escape($s) {
        $s = str_replace(['\\', '(', ')', "\r"], ['\\\\', '\\(', '\\)', ' '], $s);
        return $s;
    }

    public function SetFontSize($size) {
        $this->FontSizePt = $size;
    }

    public function GetStringWidth($s) {
        $cw = $this->GetFontWidths();
        $w = 0;
        $l = strlen($s);
        for ($i = 0; $i < $l; $i++) {
            $w += $cw[ord($s[$i])];
        }
        return $w * $this->FontSizePt / 1000;
    }

    protected function GetFontWidths() {
        return array_fill(32, 95, 600);
    }
}