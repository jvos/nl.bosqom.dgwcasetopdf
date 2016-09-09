<?php
set_time_limit(0);
ini_set('memory_limit', '512M');

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
    CRM_Casetopdf_Config::flush();
  }
  
  $configCaseToPdf = CRM_Casetopdf_Config::singleton();
  $configDgwCaseToPdf = CRM_Dgwcasetopdf_Config::singleton();
  
  $customFileUploadDir = CRM_Casetopdf_Config::getSetting('customFileUploadDir');  
  $pathname = $customFileUploadDir . 'casetopdf/';
    
  $_return = CRM_Casetopdf_Config::mkdir($pathname, 0770, false);
  if($_return['is_error']){
    $return['is_error'] = $_return['is_error'];
    $return['error_message'] = $_return['error_message'];
    $return['message'][] = $return['error_message'];
    if($debug){
      echo $return['error_message'] . '<br/>' . PHP_EOL;
    }
    return civicrm_api3_create_error($return);
    
  }
  $return['message'][] = ts('Directory created, with pathname \'%s\'.' . $pathname);
  echo ts('Directory created, with pathname \'%s\'.' . $pathname) . '<br/>' . PHP_EOL;
    
  // counting
  $query_count = "SELECT COUNT(*) AS `count` FROM civicrm_case
    LEFT JOIN civicrm_case_contact ON civicrm_case_contact.case_id = civicrm_case.id
    LEFT JOIN civicrm_contact ON civicrm_contact.id = civicrm_case_contact.contact_id
    WHERE civicrm_case.is_deleted = '0' AND civicrm_contact.is_deleted = '0'";
  
  if($dao_count = CRM_Core_DAO::executeQuery($query_count)){
    $dao_count->fetch();
    $return['message'][] = ts('\'%1\' cases retrieved.', array(1 => $dao_count->count));
    echo ts('\'%1\' cases retrieved.', array(1 => $dao_count->count)) . '<br/>' . PHP_EOL;
  }
  
  $query = "SELECT * FROM civicrm_case
    LEFT JOIN civicrm_case_contact ON civicrm_case_contact.case_id = civicrm_case.id
    LEFT JOIN civicrm_contact ON civicrm_contact.id = civicrm_case_contact.contact_id
    WHERE civicrm_case.is_deleted = '0' AND civicrm_contact.is_deleted = '0'
  ";
      
  if(!$dao = CRM_Core_DAO::executeQuery($query)){
    $return['is_error'] = true;
    $return['error_message'] = sprintf('Failed execute query (%s) !', $query);
    $return['message'][] = $return['error_message'];
    if($debug){
      echo $return['error_message'] . '<br/>' . PHP_EOL;
    }
    return civicrm_api3_create_error($return);
    
  }else {
    $return['message'][] = ts('All cases retrieved !');
    echo ts('All cases retrieved !') . '<br/>' . PHP_EOL;
  }
    
  if($debug){
    CRM_Casetopdf_Config::flush();
  }
  
  $count = 0;
  while ($dao->fetch()) { 
    if('0' != $limit and $limit == $count){
      if($debug){
        CRM_Utils_System::civiExit();
      }

      return civicrm_api3_create_success($return);
    }
    
    if('755' == $dao->case_id or '832' == $dao->case_id or '888' == $dao->case_id or '1444' == $dao->case_id or '1455' == $dao->case_id or '2695' == $dao->case_id){
      $return['message'][] = ts('Case with the id \'%1\' is skipped !', array(1 => $dao->case_id));
      if($debug){
        echo ts('Case with the id \'%1\' is skipped !', array(1 => $dao->case_id)) . '<br/>' . PHP_EOL;
      }
      continue;
    }
    
    $return['message'][] = ts('Start witth case \'%1\' with contact id \'%2\'.', array(1 => $dao->case_id, 2 => $dao->contact_id));
    if($debug){
      echo ts('Start witth case \'%1\' with contact id \'%2\'.', array(1 => $dao->case_id, 2 => $dao->contact_id)) . '<br/>' . PHP_EOL;
      CRM_Casetopdf_Config::flush();
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
    
    /*
    // het hoeft alleen te gebeuren voor alle contacten die op 1-1-2016 een actieve overeenkomst hadden
    if(isset($hov['Einddatum_HOV']) and !is_null($hov['Einddatum_HOV']) and !empty($hov['Einddatum_HOV']) and '2016-01-01' < $hov['Einddatum_HOV']){
      $return['message'][] = ts('Skip case, hov end date is before \'2016-01-01\' ! With case id \'%1\' and contact id \'%2\' and hov end date \'%3\'.', array(1 => $dao->case_id, 2 => $dao->contact_id, $hov['Einddatum_HOV']));
      echo ts('Skip case, hov end date is before \'2016-01-01\' ! With case id \'%1\' and contact id \'%2\' and hov end date \'%3\'.', array(1 => $dao->case_id, 2 => $dao->contact_id, $hov['Einddatum_HOV'])) . '<br/>' . PHP_EOL;
    }
    */
    
    $per = [];
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
    
    // case_id always 4 numbers long
    $case_id = $dao->case_id;
    $pathvar[] = $case_id;
    
    // VGE_nummer_First always 5 long
    if(isset($hov['VGE_nummer_First']) and !empty($hov['VGE_nummer_First'])){
      $VGE_nummer_First = '';
      $VGE_nummer_First = str_replace(' ', '-', $hov['VGE_nummer_First']);
      $pathvar[] = str_replace(' ', '-', $VGE_nummer_First);
    }else {
      //$pathvar[] = ts('no-vge-nr-first');
      $pathvar[] = 'none';
    }
    
    // Pesoonsnummer_First always 5 long
    if(isset($per['Persoonsnummer_First']) and !empty($per['Persoonsnummer_First'])){
      $Persoonsnummer_First = '';
      $Persoonsnummer_First = str_replace(' ', '-', $per['Persoonsnummer_First']);
      $pathvar[] = str_replace(' ', '-', $Persoonsnummer_First);
    }else {
      //$pathvar[] = ts('no-per-first');
      $pathvar[] = 'none';
    }
    
    // VGE_adres_First 255 varchar
    // vge_nummer_first 25 varchar
    /*if(isset($hov['VGE_adres_First']) and !empty($hov['VGE_adres_First'])){
      $VGE_adres_First = str_replace(' ', '-', $hov['VGE_adres_First']);
      $pathvar[] = preg_replace('/[^A-Za-z0-9\-]/', '', $VGE_adres_First); 
    }else {
      $pathvar[] = ts('no-vge-adres');
    }*/
    
    // HOV_nummer_First 25 varchar
    /*if(isset($hov['HOV_nummer_First']) and !empty($hov['HOV_nummer_First'])){
      $pathvar[] = str_replace(' ', '-', $hov['HOV_nummer_First']);
    }else {
      $pathvar[] = ts('no-hov-nr-first');
    }*/
        
    //$filename = $pathname . '(' . $dao->case_id . '_' . $dao->contact_id . ')' . implode('_', $pathvar) . '.pdf';
    $filename = $pathname . implode('_', $pathvar) . '.pdf';
        
    if(CRM_Casetopdf_Config::file_exists($filename)){
      $return['message'][] = ts('File \'%1\' already exist !', array(1 => $filename));
      if($debug){
        echo ts('File \'%1\' already exist !', array(1 => $filename)) . '<br/>' . PHP_EOL;
      }
      continue;
    }
    if(CRM_Casetopdf_Config::file_exists($pathname . implode('_', $pathvar) . '_to_big.txt')){
      $return['message'][] = ts('File \'%1\' already exist, was to big !', array(1 => $pathname . implode('_', $pathvar) . '_to_big.txt'));
      if($debug){
        echo ts('File \'%1\' already exist, was to big !', array(1 => $pathname . implode('_', $pathvar) . '_to_big.txt')) . '<br/>' . PHP_EOL;
      }
      continue;
    }
    
    $htmlcasereport = new CRM_Dgwcasetopdf_Case_XMLProcessor_Report();
    $html = '';
    $html = $htmlcasereport->htmlCaseReport($dao->case_id, $dao->contact_id);
    if(isset($html['is_error']) and $html['is_error']){
      $return['message'][] = $html['error_message'];
      if($debug){
        echo $html['error_message'] . '<br/>' . PHP_EOL;
      }
    }
    
    if($debug){
      echo ts('Html string lenght is \'%1\', of file filename \'%2\'', array(1 => strlen($html), 2 => $filename)) . '<br/>' . PHP_EOL;
      CRM_Casetopdf_Config::flush();
    }
    
    if(82710 <= strlen($html)){
      // created file to big so we can skip it easily the next time
      $_return = CRM_Casetopdf_Config::fwrite($pathname . implode('_', $pathvar) . '_to_big.txt', 'Html is to big to convert to pdf !', 'w');
      if($_return['is_error']){
        $return['message'][] = $_return['error_message'];
        if($debug){
          echo $_return['error_message'] . '<br/>' . PHP_EOL;
        }
      }
      
      $return['message'][] = ts('Html is to big to convert to pdf, filename \'%1\'', array(1 => $filename));
      if($debug){
        echo ts('Html is to big  to convert to pdf, filename \'%1\'', array(1 => $filename)) . '<br/>' . PHP_EOL;
      }
      
      continue;
    }   
    
    $output = '';
    $_return = '';
    $output = CRM_Utils_PDF_Utils::html2pdf($html, $filename, true);
    
    unset($html);    
    $_return = CRM_Casetopdf_Config::fwrite($filename, $output, 'w');
    unset($output);    
    
    if($_return['is_error']){
      $return['message'][] = $_return['error_message'];
      if($debug){
        echo $_return['error_message'] . '<br/>' . PHP_EOL;
      }

    }else {
      $return['message'][] = ts('Pdf file created, with filename \'%1\'.', array(1 => $filename));
      echo ts('Pdf file created, with filename \'%1\'.', array(1 => $filename)) . '<br/>' . PHP_EOL;
    }
        
    if($debug){
      CRM_Casetopdf_Config::flush();
    }
    
    $count++;
  }
  
  if($debug){
    CRM_Utils_System::civiExit();
  }
  
  return civicrm_api3_create_success($return);
}