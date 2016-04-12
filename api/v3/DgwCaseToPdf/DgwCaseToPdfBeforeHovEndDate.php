<?php
set_time_limit(0);

/**
 * DgwCaseToPdf.DgwCaseToPdfBeforeHovEndDate API specification (optional)
 * This is used for documentation and validation.
 *
 * @param array $spec description of fields supported by this API call
 * @return void
 * @see http://wiki.civicrm.org/confluence/display/CRMDOC/API+Architecture+Standards
 */
function _civicrm_api3_dgw_case_to_pdf_dgwcasetopdfbeforehovenddate_spec(&$spec) {
}

/**
 * DgwCaseToPdf.DgwCaseToPdfBeforeHovEndDate API
 *
 * @param array $params
 * @return array API result descriptor
 * @see civicrm_api3_create_success
 * @see civicrm_api3_create_error
 * @throws API_Exception
 */
function civicrm_api3_dgw_case_to_pdf_dgwcasetopdfbeforehovenddate($params) {
  $debug = CRM_Utils_Array::value('debug', $params, false);
  $limit = CRM_Utils_Array::value('limit', $params, '0');
    
  $return['is_error'] = false;
  $return['message'] = [];
  
  if($debug){
    $return['message'][] = ts('Debug is on !');
    echo ts('Debug is on !') . '<br/>' . PHP_EOL;
  }
  
  $configCaseToPdf = CRM_Casetopdf_Config::singleton();
  $configDgwCaseToPdf = CRM_Dgwcasetopdf_Config::singleton();
  
  $customFileUploadDir = CRM_Casetopdf_Config::getSetting('customFileUploadDir');  
  $pathname = $customFileUploadDir . 'casetopdf/';
    
  $_return = CRM_Casetopdf_Config::mkdir($pathname, 0770, false);
  if($_return['is_error']){
    $return['is_error'] = $_return['is_error'];
    $return['error_message'] = $_return['error_message'];
    if($debug){
      echo $return['error_message'] . '<br/>' . PHP_EOL;
    }
    return civicrm_api3_create_error($return);
    
  }
  $return['message'][] = ts('Directory created, with $pathname \'%s\'.' . $pathname);
    
  $query = "SELECT * FROM civicrm_case
    LEFT JOIN civicrm_case_contact ON civicrm_case_contact.case_id = civicrm_case.id
    LEFT JOIN civicrm_contact ON civicrm_contact.id = civicrm_case_contact.contact_id
    WHERE civicrm_case.is_deleted = '0' AND civicrm_contact.is_deleted = '0'
  ";
  
  if(!$dao = CRM_Core_DAO::executeQuery($query)){
    $return['is_error'] = true;
    $return['error_message'] = sprintf('Failed execute query (%s) !', $query);
    if($debug){
      echo $return['error_message'] . '<br/>' . PHP_EOL;
    }
    return civicrm_api3_create_error($return);
  }
    
  $count = 0;
  while ($dao->fetch()) { 
    if('0' != $limit and $limit == $count){
      if($debug){
        CRM_Utils_System::civiExit();
      }

      return civicrm_api3_create_success($return);
    }
    
    $household_id = $configDgwCaseToPdf->getHousehold($dao->contact_id);
    if(!$household_id or 0 == $household_id){
      $return['message'][] = ts('No household id ! With case id \'%1\' and contact id \'%2\'.', array(1 => $dao->case_id, 2 => $dao->contact_id));
      echo ts('No household id ! With case id \'%1\' and contact id \'%2\'.', array(1 => $dao->case_id, 2 => $dao->contact_id)) . '<br/>' . PHP_EOL;
    }
    
    $hoofdhuurder_id = $configDgwCaseToPdf->getHoofdhuurder($dao->contact_id);
    if(!$hoofdhuurder_id or 0 == $hoofdhuurder_id){
      $return['message'][] = ts('No hoofdhuurder id ! With case id \'%1\' and contact id \'%2\'.', array(1 => $dao->case_id, 2 => $dao->contact_id));
      echo ts('No hoofdhuurder id ! With case id \'%1\' and contact id \'%2\'.', array(1 => $dao->case_id, 2 => $dao->contact_id)) . '<br/>' . PHP_EOL;
    }
    
    $hov = [];
    if($household_id){
      $hov = $configDgwCaseToPdf->getHovHousehold($household_id);
      if(isset($hov['is_error']) and $hov['is_error']){
        $return['message'][] = $hov['error_message'];
        if($debug){
          echo $hov['error_message'] . '<br/>' . PHP_EOL;
        }
      }
    }
    
    // het hoeft alleen te gebeuren voor alle contacten die op 1-1-2016 een actieve overeenkomst hadden
    if(isset($hov['Einddatum_HOV']) and !is_null($hov['Einddatum_HOV']) and !empty($hov['Einddatum_HOV']) and '2016-01-01' < $hov['Einddatum_HOV']){
      $return['message'][] = ts('Skip case, hov end date is before \'2016-01-01\' ! With case id \'%1\' and contact id \'%2\' and hov end date \'%3\'.', array(1 => $dao->case_id, 2 => $dao->contact_id, $hov['Einddatum_HOV']));
      echo ts('Skip case, hov end date is before \'2016-01-01\' ! With case id \'%1\' and contact id \'%2\' and hov end date \'%3\'.', array(1 => $dao->case_id, 2 => $dao->contact_id, $hov['Einddatum_HOV'])) . '<br/>' . PHP_EOL;
    }
    
    if($hoofdhuurder_id){
      $per = $configDgwCaseToPdf->getPerNummerFirst($hoofdhuurder_id);
      if(isset($per['is_error']) and $per['is_error']){
        $return['message'][] = $per['error_message'];
        if($debug){
          echo $per['error_message'] . '<br/>' . PHP_EOL;
        }
      }
    }
        
    $pathvar = [];
    if(isset($per['Persoonsnummer_First']) and !empty($per['Persoonsnummer_First'])){
      $pathvar[] = str_replace(' ', '-', $per['Persoonsnummer_First']);
    }else {
      $pathvar[] = ts('no-per-first');
    }
    
    $pathvar[] = $dao->case_id;
    if(isset($hov['VGE_adres_First']) and !empty($hov['VGE_adres_First'])){
      $VGE_adres_First = str_replace(' ', '-', $hov['VGE_adres_First']);
      $pathvar[] = preg_replace('/[^A-Za-z0-9\-]/', '', $VGE_adres_First); 
    }else {
      $pathvar[] = ts('no-vge-adres');
    }
    
    if(isset($hov['HOV_nummer_First']) and !empty($hov['HOV_nummer_First'])){
      $pathvar[] = str_replace(' ', '-', $hov['HOV_nummer_First']);
    }else {
      $pathvar[] = ts('no-hov-nr-first');
    }
        
    $filename = $pathname . '(' . $dao->case_id . '_' . $dao->contact_id . ')' . implode('_', $pathvar) . '.pdf';
    
    if(CRM_Casetopdf_Config::file_exists($filename)){
      continue;
    }
    
    $htmlcasereport  = new CRM_Casetopdf_Case_XMLProcessor_Report();
    $html = $htmlcasereport->htmlCaseReport($dao->case_id, $dao->contact_id);
    if(isset($html['is_error']) and $html['is_error']){
      $return['message'][] = $html['error_message'];
      if($debug){
        echo $html['error_message'] . '<br/>' . PHP_EOL;
      }
    }
    
    $output = CRM_Utils_PDF_Utils::html2pdf($html, $filename, true);
    $_return = CRM_Casetopdf_Config::fwrite($filename, $output, 'w');
    
    if($_return['is_error']){
      $return['message'][] = $_return['error_message'];
      if($debug){
        echo $_return['error_message'] . '<br/>' . PHP_EOL;
      }

    }else {
      $return['message'][] = ts('Pdf file created, with $filename \'%1\'.', array(1 => $filename));
      echo ts('Pdf file created, with $filename \'%1\'.', array(1 => $filename)) . '<br/>' . PHP_EOL;
    }
    
    $count++;
  }
  
  if($debug){
    CRM_Utils_System::civiExit();
  }
  
  return civicrm_api3_create_success($return);
  
}

