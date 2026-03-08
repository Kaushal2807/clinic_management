<?php
/**
 * PDF Helper Class
 * Provides common PDF generation functionality with clinic branding
 */

require_once __DIR__ . '/../TCPDF-main/tcpdf.php';

class PDFHelper extends TCPDF {
    protected $clinicInfo;
    protected $showHeader = true;
    protected $showFooter = true;
    
    public function __construct($clinicInfo, $orientation = 'P', $unit = 'mm', $format = 'A4') {
        parent::__construct($orientation, $unit, $format, true, 'UTF-8', false);
        $this->clinicInfo = $clinicInfo;
        
        // Set document information
        $this->SetCreator($clinicInfo['clinic_name']);
        $this->SetAuthor($clinicInfo['clinic_name']);
        
        // Set default font
        $this->SetFont('helvetica', '', 10);
        
        // Set margins
        $this->SetMargins(15, 40, 15);
        $this->SetHeaderMargin(10);
        $this->SetFooterMargin(10);
        $this->SetAutoPageBreak(TRUE, 20);
    }
    
    /**
     * Page header
     */
    public function Header() {
        if (!$this->showHeader) {
            return;
        }
        
        // Logo
        $logo_path = !empty($this->clinicInfo['logo_path']) 
            ? __DIR__ . '/../assets/uploads/logos/' . $this->clinicInfo['logo_path']
            : null;
        
        if ($logo_path && file_exists($logo_path)) {
            $this->Image($logo_path, 15, 10, 25, 0, '', '', 'T', false, 300, '', false, false, 0, false, false, false);
            $this->SetX(45);
        } else {
            $this->SetX(15);
        }
        
        // Clinic info
        $this->SetY(10);
        $this->SetFont('helvetica', 'B', 16);
        $this->SetTextColor(67, 56, 202); // Indigo
        $this->Cell(0, 8, $this->clinicInfo['clinic_name'], 0, 1, 'R');
        
        $this->SetFont('helvetica', '', 9);
        $this->SetTextColor(100, 100, 100);
        
        if (!empty($this->clinicInfo['address'])) {
            $this->Cell(0, 5, $this->clinicInfo['address'], 0, 1, 'R');
        }
        
        $contact = [];
        if (!empty($this->clinicInfo['contact_phone'])) {
            $contact[] = 'Tel: ' . $this->clinicInfo['contact_phone'];
        }
        if (!empty($this->clinicInfo['contact_email'])) {
            $contact[] = 'Email: ' . $this->clinicInfo['contact_email'];
        }
        if (!empty($contact)) {
            $this->Cell(0, 5, implode(' | ', $contact), 0, 1, 'R');
        }
        
        // Line separator
        $this->SetY(35);
        $this->SetDrawColor(67, 56, 202);
        $this->SetLineWidth(0.5);
        $this->Line(15, 35, $this->getPageWidth() - 15, 35);
        
        $this->SetTextColor(0, 0, 0);
        $this->Ln(5);
    }
    
    /**
     * Page footer
     */
    public function Footer() {
        if (!$this->showFooter) {
            return;
        }
        
        $this->SetY(-15);
        $this->SetFont('helvetica', 'I', 8);
        $this->SetTextColor(128, 128, 128);
        
        // Divider line
        $this->Line(15, $this->GetY(), $this->getPageWidth() - 15, $this->GetY());
        $this->Ln(2);
        
        // Footer text
        $this->Cell(0, 5, $this->clinicInfo['clinic_name'] . ' - Generated on ' . date('d M Y, h:i A'), 0, 0, 'L');
        $this->Cell(0, 5, 'Page ' . $this->getAliasNumPage() . ' of ' . $this->getAliasNbPages(), 0, 0, 'R');
    }
    
    /**
     * Disable header
     */
    public function disableHeader() {
        $this->showHeader = false;
        $this->setPrintHeader(false);
        $this->SetMargins(15, 15, 15);
    }
    
    /**
     * Disable footer
     */
    public function disableFooter() {
        $this->showFooter = false;
        $this->setPrintFooter(false);
    }
    
    /**
     * Create section header
     */
    public function addSectionHeader($title, $bgColor = [67, 56, 202], $textColor = [255, 255, 255]) {
        $this->SetFillColor($bgColor[0], $bgColor[1], $bgColor[2]);
        $this->SetTextColor($textColor[0], $textColor[1], $textColor[2]);
        $this->SetFont('helvetica', 'B', 12);
        $this->Cell(0, 8, $title, 0, 1, 'L', true);
        $this->SetTextColor(0, 0, 0);
        $this->Ln(2);
    }
    
    /**
     * Create info box
     */
    public function addInfoBox($title, $data, $bgColor = [240, 240, 255]) {
        $this->SetFillColor($bgColor[0], $bgColor[1], $bgColor[2]);
        $this->SetFont('helvetica', 'B', 10);
        $this->Cell(0, 6, $title, 0, 1, 'L', true);
        
        $this->SetFont('helvetica', '', 9);
        foreach ($data as $label => $value) {
            $this->Cell(40, 5, $label . ':', 0, 0);
            $this->SetFont('helvetica', 'B', 9);
            $this->Cell(0, 5, $value, 0, 1);
            $this->SetFont('helvetica', '', 9);
        }
        $this->Ln(3);
    }
    
    /**
     * Create table
     */
    public function addTable($headers, $rows, $widths = null) {
        // Calculate widths if not provided
        if ($widths === null) {
            $totalWidth = $this->getPageWidth() - 30; // Account for margins
            $colWidth = $totalWidth / count($headers);
            $widths = array_fill(0, count($headers), $colWidth);
        }
        
        // Header
        $this->SetFillColor(67, 56, 202);
        $this->SetTextColor(255, 255, 255);
        $this->SetFont('helvetica', 'B', 9);
        
        foreach ($headers as $i => $header) {
            $this->Cell($widths[$i], 7, $header, 1, 0, 'C', true);
        }
        $this->Ln();
        
        // Data rows
        $this->SetFillColor(245, 245, 245);
        $this->SetTextColor(0, 0, 0);
        $this->SetFont('helvetica', '', 9);
        
        $fill = false;
        foreach ($rows as $row) {
            foreach ($row as $i => $cell) {
                $this->Cell($widths[$i], 6, $cell, 1, 0, 'L', $fill);
            }
            $this->Ln();
            $fill = !$fill;
        }
        $this->Ln(3);
    }
    
    /**
     * Add signature section
     */
    public function addSignatureSection($doctorName = null) {
        $this->Ln(10);
        $this->SetFont('helvetica', '', 10);
        
        $this->Cell(0, 5, 'Doctor\'s Signature', 0, 1, 'R');
        $this->Ln(15);
        
        $this->SetDrawColor(0, 0, 0);
        $this->Line($this->getPageWidth() - 70, $this->GetY(), $this->getPageWidth() - 15, $this->GetY());
        $this->Ln(2);
        
        if ($doctorName) {
            $this->Cell(0, 5, $doctorName, 0, 1, 'R');
        }
    }
}
