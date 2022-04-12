<?php

namespace Drupal\reserve_globo_modelo;

use Drupal\webform\WebformSubmissionForm;
use Drupal\webform\Entity\Webform; 
use Drupal\webform\Entity\WebformSubmission;


class ReserveGloboModeloManager implements ReserveGloboModeloManagerInterface {
  
  public $config;

  public function __construct() {
    $this->config = \Drupal::config('reserve_globo_modelo.settings');
  }

  public function typesFlights() {
    return ['c' => 'Vuelo Cautivo', 'l' => 'Vuelo Libre'];
  }

  public function statesFlights() {
    return ['p' => 'En confirmación', 'o' => 'Confirmada', 'c' => 'Cancelada'];
  }

  public function daysList() {
    $bd = [];
    for ($j=1; $j < 32; $j++) { 
      $bd[$j] = $j;
    }
    return $bd;
  }

  public function monthsList() {
    return ['1' => 'Enero', '2' => 'Febrero', '3' => 'Marzo', '4' => 'Abril', '5' => 'Mayo', '6' => 'Junio', '7' => 'Julio', '8' => 'Agosto', '9' => 'Septiembre', '10' => 'Octubre', '11' => 'Noviembre', '12' => 'Diciembre'];
  }

  public function yearsList() {
    $by = [];
    for ($j=1980; $j < 2022; $j++) { 
      $by[$j] = $j;
    }
    return $by;
  }

  public function citysList() {
    return ['Alta Verapaz' => 'Alta Verapaz', 'Baja Verapaz' => 'Baja Verapaz', 'Chimaltenango' => 'Chimaltenango', 'Chiquimula' => 'Chiquimula', 'El Progreso' => 'El Progreso', 'Escuintla' => 'Escuintla', 'Guatemala' => 'Guatemala', 'Huehuetenango' => 'Huehuetenango', 'Izabal' => 'Izabal', 'Jalapa' => 'Jalapa', 'Jutiapa' => 'Jutiapa', 'Petén' => 'Petén', 'Quetzaltenango' => 'Quetzaltenango', 'Quiché' => 'Quiché', 'Retalhuleu' => 'Retalhuleu', 'Sacatepéquez' => 'Sacatepéquez', 'San Marcos' => 'San Marcos', 'Santa Rosa' => 'Santa Rosa', 'Sololá' => 'Sololá', 'Suchitepéquez' => 'Suchitepéquez', 'Totonicapán' => 'Totonicapán', 'Zacapa' => 'Zacapa'];
  }

  public function weightsList() {
    return ['56' => '56'];
  }
  
  public function gendersList() {
    return ['Male' => 'Hombre', 'Female' => 'Mujer', 'Other' => 'Prefiero no decir'];
  }

  public function saveDataWebForm($webform_id, $data) {
    $values = [
      'webform_id' => $webform_id,
      'entity_type' => NULL,
      'entity_id' => NULL,
      'in_draft' => FALSE,
      'uid' => '1',
      'langcode' => 'es',
      'token' => 'pgmJREX2l4geg2RGFp0p78Qdfm1ksLxe6IlZ-mN9GZI',
      'uri' => '/webform/'.$webform_id.'/api',
      'remote_addr' => '',
      'data' => $data,
    ];
    // Check webform is open.
    $webform = Webform::load($values['webform_id']);
    $is_open = WebformSubmissionForm::isOpen($webform);

    if ($is_open === TRUE) {
      // Validate submission.
      $errors = WebformSubmissionForm::validateFormValues($values);
      // Check there are no validation errors.
      if (!empty($errors)) {
        return $errors;
      }
      else {
        // Submit values and get submission ID.
        $webform_submission = WebformSubmissionForm::submitFormValues($values);
        return $webform_submission->id();
      }
    }
  }

  public function queryDataWebForm($webform_id, $query='') {
    $response = [];
    $select = \Drupal::service('database')
    ->select('webform_submission_data', 'wsd')
    ->fields('wsd', array('sid'))
    ->condition('wsd.webform_id', $webform_id, '=')
    ->condition('wsd.name', 'id_reserve', '=')
    ->condition('wsd.value', $query, '=')
    ->execute();
    $results = $select->fetchAll(\PDO::FETCH_COLUMN);
    if(!empty($results)) {
      foreach ($results as $key => $value) {
        $submission = WebformSubmission::load($value);
        $submission_data = $submission->getData();
        $submission_data['sid'] = $value;
        $response[] = $submission_data;
      }
    }
    return $response;
  }

  public function queryAvailabilityFlightsWebForm($webform_id, $date, $participants) {
    $response = true;
    $select = \Drupal::service('database')
    ->select('webform_submission_data', 'wsd')
    ->fields('wsd', array('sid'))
    ->condition('wsd.webform_id', $webform_id, '=')
    ->condition('wsd.name', 'date', '=')
    ->condition('wsd.value', $date, '=')
    ->execute();
    $results = $select->fetchAll(\PDO::FETCH_COLUMN);
    if(!empty($results)) {
      foreach ($results as $key => $value) {
        $submission = WebformSubmission::load($value);
        $submission_data = $submission->getData();
        $submission_data['sid'] = $value;
        $data[] = $submission_data;
      }
    }
    $limit = 8;
    $participants_number = 0;
    if(!empty($data)) {
      foreach ($data as $key => $value) {
        $participants_number = $participants_number + $value['participants'];
      }
      $diff = $limit - $participants_number;
    }else {
      $diff = $limit;
    }
    if($diff < 0){
      $response = false;
    }else if($participants > $diff) {
      $response = false;
    }
    return $response;
  }

  public function generateToken($strength = 8) {
    $input = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $input_length = strlen($input);
    $token = '';
    for($i = 0; $i < $strength; $i++) {
      $random_character = $input[mt_rand(0, $input_length - 1)];
      $token .= $random_character;
    }
    return $token;
  }

  public function purchaseSummary($flight, $date, $participants) {
    $participants = intval($participants);
    $total = $this->calculatePrice($participants, $flight);
    $types_flights = $this->typesFlights();
    
    $html =  '<div class="summary-info">
      <span>Tipo de Vuelo: </span>'.$types_flights[$flight].'<br>
      <span>Fecha: </span>'.$date.'<br>
      <span>Participantes: </span>'.$participants.'<br>
      <div class="total">
        <span>Total: </span>'.$total.' Dolares
      </div>
    </div>
    ';
    
    return $html;
  }

  public function calculatePrice($participants, $flight) {
    $config = $this->config->get('reserve_globo_modelo.flights');
    $participants = intval($participants);
    $price = intval($config[$flight]['price']);
    return $total = $price * $participants;
  }
}
