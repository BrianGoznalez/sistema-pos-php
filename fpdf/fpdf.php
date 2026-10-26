<?php
/*******************************************************************************
* FPDF                                                                         *
* Version: 1.84                                                                *
* Author:  Olivier Plathey                                                     *
*******************************************************************************/

define('FPDF_VERSION','1.84');

class FPDF
{
protected $page;
protected $n;
protected $offsets;
protected $buffer;
protected $pages;
protected $state;
protected $compress;
protected $k;
protected $DefOrientation;
protected $CurOrientation;
protected $StdPageSizes;
protected $DefPageSize;
protected $CurPageSize;
protected $CurRotation;
protected $PageInfo;
protected $wPt, $hPt;
protected $w, $h;
protected $lMargin;
protected $tMargin;
protected $rMargin;
protected $bMargin;
protected $cMargin;
protected $x, $y;
protected $lasth;
protected $LineWidth;
protected $fontpath;
protected $CoreFonts;
protected $fonts;
protected $FontFiles;
protected $encodings;
protected $cmaps;
protected $FontFamily;
protected $FontStyle;
protected $underline;
protected $CurrentFont;
protected $FontSizePt;
protected $FontSize;
protected $DrawColor;
protected $FillColor;
protected $TextColor;
protected $ColorFlag;
protected $WithAlpha;
protected $ws;
protected $images;
protected $PageLinks;
protected $links;
protected $AutoPageBreak;
protected $PageBreakTrigger;
protected $InHeader;
protected $InFooter;
protected $AliasNbPages;
protected $ZoomMode;
protected $LayoutMode;
protected $metadata;
protected $PDFVersion;

function __construct($orientation='P', $unit='mm', $size='A4')
{
	$this->_dochecks();
	$this->state = 0;
	$this->page = 0;
	$this->n = 2;
	$this->buffer = '';
	$this->pages = array();
	$this->PageInfo = array();
	$this->fonts = array();
	$this->FontFiles = array();
	$this->encodings = array();
	$this->cmaps = array();
	$this->images = array();
	$this->links = array();
	$this->InHeader = false;
	$this->InFooter = false;
	$this->lasth = 0;
	$this->FontFamily = '';
	$this->FontStyle = '';
	$this->FontSizePt = 12;
	$this->underline = false;
	$this->DrawColor = '0 G';
	$this->FillColor = '0 g';
	$this->TextColor = '0 g';
	$this->ColorFlag = false;
	$this->WithAlpha = false;
	$this->ws = 0;
	$this->CoreFonts = array('courier', 'helvetica', 'times', 'symbol', 'zapfdingbats');

	if($unit=='pt') $this->k = 1;
	elseif($unit=='mm') $this->k = 72/25.4;
	elseif($unit=='cm') $this->k = 72/2.54;
	elseif($unit=='in') $this->k = 72;
	else $this->Error('Incorrect unit: '.$unit);

	$this->StdPageSizes = array('a3'=>array(841.89, 1190.55), 'a4'=>array(595.28, 841.89), 'a5'=>array(420.94, 595.28),
		'letter'=>array(612, 792), 'legal'=>array(612, 1008));
	$size = $this->_getpagesize($size);
	$this->DefPageSize = $size;
	$this->CurPageSize = $size;

	$orientation = strtolower($orientation);
	if($orientation=='p' || $orientation=='portrait') {
		$this->DefOrientation = 'P';
		$this->w = $size[0];
		$this->h = $size[1];
	} elseif($orientation=='l' || $orientation=='landscape') {
		$this->DefOrientation = 'L';
		$this->w = $size[1];
		$this->h = $size[0];
	} else $this->Error('Incorrect orientation: '.$orientation);
	
	$this->CurOrientation = $this->DefOrientation;
	$this->wPt = $this->w*$this->k;
	$this->hPt = $this->h*$this->k;
	$this->CurRotation = 0;
	$margin = 28.35/$this->k;
	$this->SetMargins($margin, $margin);
	$this->cMargin = $margin/10;
	$this->LineWidth = .567/$this->k;
	$this->SetAutoPageBreak(true, 2*$margin);
	$this->SetDisplayMode('default');
	$this->compress = true;
	$this->PDFVersion = '1.3';
}

function SetMargins($left, $top, $right=null)
{
	$this->lMargin = $left;
	$this->tMargin = $top;
	if($right===null) $right = $left;
	$this->rMargin = $right;
}

function SetLeftMargin($margin)
{
	$this->lMargin = $margin;
	if($this->page>0 && $this->x<$margin) $this->x = $margin;
}

function SetTopMargin($margin)
{
	$this->tMargin = $margin;
}

function SetRightMargin($margin)
{
	$this->rMargin = $margin;
}

function SetAutoPageBreak($auto, $margin=0)
{
	$this->AutoPageBreak = $auto;
	$this->bMargin = $margin;
	$this->PageBreakTrigger = $this->h-$margin;
}

function SetDisplayMode($zoom, $layout='default')
{
	if($zoom=='fullpage' || $zoom=='fullwidth' || $zoom=='real' || $zoom=='default' || !is_string($zoom))
		$this->ZoomMode = $zoom;
	else $this->Error('Incorrect zoom display mode: '.$zoom);
	if($layout=='single' || $layout=='continuous' || $layout=='two' || $layout=='default')
		$this->LayoutMode = $layout;
	else $this->Error('Incorrect layout display mode: '.$layout);
}

function SetCompression($compress)
{
	if(function_exists('gzcompress')) $this->compress = $compress;
	else $this->compress = false;
}

function Error($msg)
{
	throw new Exception('FPDF error: '.$msg);
}

function AddPage($orientation='', $size='', $rotation=0)
{
	if($this->state==3) $this->Error('The document is closed');
	$family = $this->FontFamily;
	$style = $this->FontStyle.($this->underline ? 'U' : '');
	$fontsize = $this->FontSizePt;
	$lw = $this->LineWidth;
	$dc = $this->DrawColor;
	$fc = $this->FillColor;
	$tc = $this->TextColor;
	$cf = $this->ColorFlag;

	if($this->page>0) {
		$this->InFooter = true;
		$this->Footer();
		$this->InFooter = false;
		$this->_endpage();
	}

	$this->_beginpage($orientation, $size, $rotation);
	$this->_out('2 J');
	$this->LineWidth = $lw;
	$this->_out(sprintf('%.2F w', $lw*$this->k));
	if($family) $this->SetFont($family, $style, $fontsize);
	$this->DrawColor = $dc;
	if($dc!='0 G') $this->_out($dc);
	$this->FillColor = $fc;
	if($fc!='0 g') $this->_out($fc);
	$this->TextColor = $tc;
	$this->ColorFlag = $cf;

	$this->InHeader = true;
	$this->Header();
	$this->InHeader = false;

	if($this->LineWidth!=$lw) {
		$this->LineWidth = $lw;
		$this->_out(sprintf('%.2F w', $lw*$this->k));
	}
	if($family) $this->SetFont($family, $style, $fontsize);
	if($this->DrawColor!=$dc) { $this->DrawColor = $dc; $this->_out($dc); }
	if($this->FillColor!=$fc) { $this->FillColor = $fc; $this->_out($fc); }
	$this->TextColor = $tc;
	$this->ColorFlag = $cf;
}

function Header() {}
function Footer() {}
function PageNo() { return $this->page; }

function SetDrawColor($r, $g=null, $b=null)
{
	if(($r==0 && $g==0 && $b==0) || $g===null) $this->DrawColor = sprintf('%.3F G', $r/255);
	else $this->DrawColor = sprintf('%.3F %.3F %.3F RG', $r/255, $g/255, $b/255);
	if($this->page>0) $this->_out($this->DrawColor);
}

function SetFillColor($r, $g=null, $b=null)
{
	if(($r==0 && $g==0 && $b==0) || $g===null) $this->FillColor = sprintf('%.3F g', $r/255);
	else $this->FillColor = sprintf('%.3F %.3F %.3F rg', $r/255, $g/255, $b/255);
	$this->ColorFlag = ($this->FillColor!=$this->TextColor);
	if($this->page>0) $this->_out($this->FillColor);
}

function SetTextColor($r, $g=null, $b=null)
{
	if(($r==0 && $g==0 && $b==0) || $g===null) $this->TextColor = sprintf('%.3F g', $r/255);
	else $this->TextColor = sprintf('%.3F %.3F %.3F rg', $r/255, $g/255, $b/255);
	$this->ColorFlag = ($this->FillColor!=$this->TextColor);
}

function SetFont($family, $style='', $size=0)
{
	if($family=='') $family = $this->FontFamily;
	else $family = strtolower($family);
	$style = strtoupper($style);
	if(strpos($style, 'U')!==false) {
		$this->underline = true;
		$style = str_replace('U', '', $style);
	} else $this->underline = false;
	if($style=='IB') $style = 'BI';
	if($size==0) $size = $this->FontSizePt;

	if($this->FontFamily==$family && $this->FontStyle==$style && $this->FontSizePt==$size) return;

	$fontkey = $family.$style;
	if(!isset($this->fonts[$fontkey])) {
		if($family=='arial') $family = 'helvetica';
		if(in_array($family, $this->CoreFonts)) {
			if($family=='symbol' || $family=='zapfdingbats') $style = '';
			$fontkey = $family.$style;
			if(!isset($this->fonts[$fontkey])) {
				$i = count($this->fonts)+1;
				$name = ($family=='helvetica' ? 'Helvetica' : ($family=='times' ? 'Times-Roman' : 'Courier'));
				if($style=='B') $name .= '-Bold';
				elseif($style=='I') $name .= '-Oblique';
				elseif($style=='BI') $name .= '-BoldOblique';
				$this->fonts[$fontkey] = array('i'=>$i, 'type'=>'Core', 'name'=>$name, 'up'=>-100, 'ut'=>50);
			}
		} else $this->Error('Undefined font: '.$family.' '.$style);
	}

	$this->FontFamily = $family;
	$this->FontStyle = $style;
	$this->FontSizePt = $size;
	$this->FontSize = $size/$this->k;
	$this->CurrentFont = $this->fonts[$fontkey];
	if($this->page>0) $this->_out(sprintf('BT /F%d %.2F Tf ET', $this->CurrentFont['i'], $this->FontSizePt));
}

function SetFontSize($size)
{
	if($this->FontSizePt==$size) return;
	$this->FontSizePt = $size;
	$this->FontSize = $size/$this->k;
	if($this->page>0 && isset($this->CurrentFont))
		$this->_out(sprintf('BT /F%d %.2F Tf ET', $this->CurrentFont['i'], $this->FontSizePt));
}

function GetX() { return $this->x; }
function SetX($x) { if($x>=0) $this->x = $x; else $this->x = $this->w+$x; }
function GetY() { return $this->y; }
function SetY($y, $resetX=true)
{
	if($y>=0) $this->y = $y;
	else $this->y = $this->h+$y;
	if($resetX) $this->x = $this->lMargin;
}

function Cell($w, $h=0, $txt='', $border=0, $ln=0, $align='', $fill=false)
{
	$k = $this->k;
	if($this->y+$h>$this->PageBreakTrigger && !$this->InHeader && !$this->InFooter && $this->AutoPageBreak) {
		$x = $this->x;
		$this->AddPage($this->CurOrientation, $this->CurPageSize, $this->CurRotation);
		$this->x = $x;
	}
	if($w==0) $w = $this->w-$this->rMargin-$this->x;
	$s = '';
	if($fill || $border==1) {
		$op = $fill ? ($border==1 ? 'B' : 'f') : 'S';
		$s = sprintf('%.2F %.2F %.2F %.2F re %s ', $this->x*$k, ($this->h-$this->y)*$k, $w*$k, -$h*$k, $op);
	}
	$txt = (string)$txt;
	if($txt!=='') {
		if($align=='R') $dx = $w-$this->cMargin-($this->FontSizePt*strlen($txt)*0.5/$this->k);
		elseif($align=='C') $dx = ($w-($this->FontSizePt*strlen($txt)*0.5/$this->k))/2;
		else $dx = $this->cMargin;
		if($this->ColorFlag) $s .= 'q '.$this->TextColor.' ';
		$s .= sprintf('BT %.2F %.2F Td (%s) Tj ET', ($this->x+$dx)*$k, ($this->h-($this->y+.5*$h+.3*$this->FontSize))*$k, str_replace(array('\\','(',')'), array('\\\\','\\(','\\)'), $txt));
		if($this->ColorFlag) $s .= ' Q';
	}
	if($s) $this->_out($s);
	$this->lasth = $h;
	if($ln>0) {
		$this->y += $h;
		if($ln==1) $this->x = $this->lMargin;
	} else $this->x += $w;
}

function Ln($h=null)
{
	$this->x = $this->lMargin;
	if($h===null) $this->y += $this->lasth;
	else $this->y += $h;
}

function Image($file, $x=null, $y=null, $w=0, $h=0)
{
	if(!isset($this->images[$file])) {
		$a = getimagesize($file);
		if(!$a) $this->Error('Missing or incorrect image file: '.$file);
		$info = array('w'=>$a[0], 'h'=>$a[1], 'i'=>count($this->images)+1, 'data'=>file_get_contents($file));
		$this->images[$file] = $info;
	} else $info = $this->images[$file];

	if($w==0 && $h==0) { $w = -96; $h = -96; }
	if($w<0) $w = -$info['w']*72/$w/$this->k;
	if($h<0) $h = -$info['h']*72/$h/$this->k;
	if($w==0) $w = $h*$info['w']/$info['h'];
	if($h==0) $h = $w*$info['h']/$info['w'];

	if($y===null) $y = $this->y;
	if($x===null) $x = $this->x;
	$this->_out(sprintf('q %.2F 0 0 %.2F %.2F %.2F cm /I%d Do Q', $w*$this->k, $h*$this->k, $x*$this->k, ($this->h-($y+$h))*$this->k, $info['i']));
}

function Output($dest='', $name='')
{
	if($this->page==0) $this->AddPage();
	$this->InFooter = true; $this->Footer(); $this->InFooter = false;
	$this->_endpage();
	$this->_enddoc();

	if(PHP_SAPI!='cli') {
		header('Content-Type: application/pdf');
		header('Content-Disposition: inline; filename="'.($name ? $name : 'doc.pdf').'"');
		header('Cache-Control: private, max-age=0, must-revalidate');
		header('Pragma: public');
	}
	echo $this->buffer;
}

protected function _dochecks() {}
protected function _getpagesize($size)
{
	if(is_string($size)) {
		$size = strtolower($size);
		if(!isset($this->StdPageSizes[$size])) $this->Error('Unknown page size: '.$size);
		$a = $this->StdPageSizes[$size];
		return array($a[0]/$this->k, $a[1]/$this->k);
	} else return array($size[0], $size[1]);
}

protected function _beginpage($orientation, $size, $rotation)
{
	$this->page++;
	$this->pages[$this->page] = '';
	$this->state = 2;
	$this->x = $this->lMargin;
	$this->y = $this->tMargin;
	$this->FontFamily = '';
}

protected function _endpage() { $this->state = 1; }

protected function _out($s)
{
	if($this->state==2) $this->pages[$this->page] .= $s."\n";
}

protected function _put($s) { $this->buffer .= $s."\n"; }

protected function _enddoc()
{
	$nb = $this->page;
	$this->_put('%PDF-1.3');
	for($i=1;$i<=$nb;$i++) {
		$this->_put(($i+2).' 0 obj');
		$this->_put('<</Type /Page /Parent 1 0 R /Resources 2 0 R /Contents '.($nb+$i+2).' 0 R>>');
		$this->_put('endobj');
	}
	for($i=1;$i<=$nb;$i++) {
		$p = $this->pages[$i];
		$this->_put(($nb+$i+2).' 0 obj');
		$this->_put('<</Length '.strlen($p).'>>');
		$this->_put('stream');
		$this->_put($p);
		$this->_put('endstream');
		$this->_put('endobj');
	}
	$nf = $nb*2 + 2;
	foreach($this->fonts as $font) {
		$nf++;
		$this->_put($nf.' 0 obj');
		$this->_put('<</Type /Font /BaseFont /'.$font['name'].' /Subtype /Type1>>');
		$this->_put('endobj');
	}
	foreach($this->images as $info) {
		$nf++;
		$this->_put($nf.' 0 obj');
		$this->_put('<</Type /XObject /Subtype /Image /Width '.$info['w'].' /Height '.$info['h'].' /ColorSpace /DeviceRGB /BitsPerComponent 8 /Filter /DCTDecode /Length '.strlen($info['data']).'>>');
		$this->_put('stream');
		$this->_put($info['data']);
		$this->_put('endstream');
		$this->_put('endobj');
	}
	$this->_put('2 0 obj');
	$this->_put('<</ProcSet [/PDF /Text /ImageC] /Font <<');
	foreach($this->fonts as $font) $this->_put('/F'.$font['i'].' '.($nb*2 + 2 + $font['i']).' 0 R');
	$this->_put('>> /XObject <<');
	foreach($this->images as $info) $this->_put('/I'.$info['i'].' '.($nb*2 + 2 + count($this->fonts) + $info['i']).' 0 R');
	$this->_put('>>>>');
	$this->_put('endobj');

	$this->_put('1 0 obj');
	$this->_put('<</Type /Pages /Count '.$nb.' /Kids [');
	for($i=1;$i<=$nb;$i++) $this->_put(($i+2).' 0 R');
	$this->_put('] /MediaBox [0 0 '.$this->wPt.' '.$this->hPt.']>>');
	$this->_put('endobj');

	$this->_put('3 0 obj');
	$this->_put('<</Type /Catalog /Pages 1 0 R>>');
	$this->_put('endobj');
}
}
?>