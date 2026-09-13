<?php
declare(strict_types=1);
/** Excel cells are explicit text so identifiers retain zeros and cannot execute formulas. */
function finance_export(string $name,array $headers,iterable $rows,string $format): never
{
    if(!in_array($format,['csv','xlsx'],true))throw new InvalidArgumentException('Invalid export format.');
    header('Cache-Control: private, no-store');
    if($format==='csv'){
        header('Content-Type: text/csv; charset=utf-8');header('Content-Disposition: attachment; filename="'.$name.'.csv"');$out=fopen('php://output','w');fwrite($out,"\xEF\xBB\xBF");fputcsv($out,$headers,',','"','');foreach($rows as $row)fputcsv($out,csv_row($row),',','"','');fclose($out);exit;
    }
    if(!class_exists('ZipArchive'))throw new RuntimeException('XLSX requires the PHP zip extension.');
    $sheet=tempnam(sys_get_temp_dir(),'wallet-sheet-');$archive=tempnam(sys_get_temp_dir(),'wallet-export-');
    try{
        $out=fopen($sheet,'wb');fwrite($out,'<?xml version="1.0" encoding="UTF-8"?><worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetData>');
        $write=static function(array $row)use($out):void{fwrite($out,'<row>');foreach($row as $cell){$value=preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/','',(string)$cell);fwrite($out,'<c t="inlineStr"><is><t xml:space="preserve">'.htmlspecialchars($value,ENT_XML1|ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8').'</t></is></c>');}fwrite($out,'</row>');};
        $write($headers);foreach($rows as $row)$write($row);fwrite($out,'</sheetData></worksheet>');fclose($out);
        $zip=new ZipArchive();if($zip->open($archive,ZipArchive::OVERWRITE)!==true)throw new RuntimeException('Could not create export.');
        $zip->addFromString('[Content_Types].xml','<?xml version="1.0"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/></Types>');
        $zip->addFromString('_rels/.rels','<?xml version="1.0"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>');
        $zip->addFromString('xl/workbook.xml','<?xml version="1.0"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="Ledger" sheetId="1" r:id="rId1"/></sheets></workbook>');
        $zip->addFromString('xl/_rels/workbook.xml.rels','<?xml version="1.0"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/></Relationships>');
        $zip->addFile($sheet,'xl/worksheets/sheet1.xml');if(!$zip->close())throw new RuntimeException('Could not finish export.');
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');header('Content-Disposition: attachment; filename="'.$name.'.xlsx"');readfile($archive);
    }finally{if(is_file($sheet))unlink($sheet);if(is_file($archive))unlink($archive);}exit;
}
