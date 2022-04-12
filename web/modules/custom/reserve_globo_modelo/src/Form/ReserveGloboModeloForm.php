<?php

namespace Drupal\reserve_globo_modelo\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Ajax\InvokeCommand;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\reserve_globo_modelo\ReserveGloboModeloManagerInterface;

/**
 * Declaration of class ReserveGloboModeloForm.
 */
class ReserveGloboModeloForm extends FormBase {

  protected $commonFunction;
  public $types_flights;

  public function __construct(ReserveGloboModeloManagerInterface $interface) {
    $this->commonFunction = $interface;
    $this->types_flights = $this->commonFunction->typesFlights();
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('reserve_globo_modelo.manager')
    );
  }

  public function getFormId() {
    return 'reserve_globo_modelo_form';
  }

  protected function getEditableConfigNames() {
    return [
      'reserve_globo_modelo.settings',
    ];
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['#tree'] = TRUE;  

    $form['#disable_inline_form_errors'] = TRUE;

    $form['container'] = [
        '#type' => 'container',
        '#attributes' => ['id' => 'ajax-response-container'],
    ];

    $form['container']['widget'] = array(
      '#type' => 'fieldset',
    );

    $flight = isset($_REQUEST['f']) ? $_REQUEST['f'] : 'c';
    $form['container']['widget']['flight'] =[
      '#type' => 'radios',
      '#options' => $this->types_flights,
      '#default_value' => $flight,
      '#required' => TRUE,
      '#ajax' => [
        'callback' => '::widgetFlightCallback',
        'wrapper' => 'ajax-response-container',
        'event' => 'change',
      ],
      '#weight' => 0,
      '#prefix' => '<div class="flight">',
      '#suffix' => '<span class="flight-valid-message"></span></div>',
    ];
    $date = isset($_REQUEST['d']) ? $_REQUEST['d'] : '';
    $form['container']['widget']['date'] = [
      '#type' => 'date',
      '#default_value' => $date,
      '#required' => TRUE,
      '#weight' => 0,
      '#prefix' => '<div class="date">',
      '#suffix' => '<span class="date-valid-message"></span></div>',
    ];
    $participants = isset($_REQUEST['p']) ? $_REQUEST['p'] : '0';
    for ($j=1; $j < 9; $j++) { 
      $p[$j] = $j;
    }
    $form['container']['widget']['participants'] = [
      '#type' => 'select',
      '#options' => $p,
      '#default_value' => $participants,
      '#required' => TRUE,
      '#ajax' => [
        'callback' => '::widgetFlightCallback',
        'wrapper' => 'ajax-response-container',
        'event' => 'change',
      ],
      '#weight' => 0,
      '#prefix' => '<div class="participants">',
      '#suffix' => '<span class="participants-valid-message"></span></div>',
    ];

    if(!empty($participants)){
      $participants_number = intval($participants);
      if($participants !== $form_state->getUserInput()['container']['widget']['participants']){
        $participants_number = isset($form_state->getUserInput()['container']['widget']['participants']) ? intval($form_state->getUserInput()['container']['widget']['participants']) : 1;
      }
    }else {
      $participants_number = isset($form_state->getUserInput()['container']['widget']['participants']) ? intval($form_state->getUserInput()['container']['widget']['participants']) : 1;
    }

    $flight_type = isset($form_state->getUserInput()['container']['widget']['flight']) ? intval($form_state->getUserInput()['container']['widget']['flight']) : '';
    $flight_date = isset($form_state->getUserInput()['container']['widget']['date']) ? intval($form_state->getUserInput()['container']['widget']['date']) : '';

    $form['container']['summary'] = [
      '#type' => 'fieldset',
      '#title' => t('Resumen de Compra'),
      '#prefix' => '<div class="summary">',
      '#suffix' => '</div>',
    ];
    $html = '';
    if(!empty($flight) && !empty($date) && !empty($participants)){
      $html =  $this->commonFunction->purchaseSummary($flight, $date, $participants);
    }
    $form['container']['summary']['html'] = [
      '#type' => 'markup',
      '#markup' => $html,
    ];
    $form['container']['summary']['terms_of_service'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Acepto términos y condiciones y políticas y privacidad.'),
      '#required' => TRUE,
      '#default_value' => '',
      '#prefix' => '<div class="terms_of_service">',
      '#suffix' => '<span class="terms_of_service-valid-message"></span></div>',
    ];
    $form['container']['summary']['marketing'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Deseo recibir información comercial y eventos de MODELO.'),
      '#default_value' => '',
      '#prefix' => '<div class="marketing">',
      '#suffix' => '<span class="marketing-valid-message"></span></div>',
    ];
    $form['container']['summary']['actions']['#type'] = 'actions';
    $form['container']['summary']['actions']['submit'] = [
     '#type' => 'submit',
     '#value' => $this->t('Reservar Ahora'),
     '#ajax' => [
        'callback' => '::setMessage'
      ],
    ];

    for ($i = 0; $i < $participants_number; $i++) {
      $part = $i+1;
      if($i == 0){
        $label = 'Datos '.$part.' Participante';
        $form['container']['participant_'.$i] = array(
          '#type' => 'fieldset',
          '#title' => $label,
        );
        $form['container']['participant_'.$i]['name'] = [
          '#type' => 'textfield',
          '#title' => $this->t('Nombre'),
          '#placeholder' => 'Ingresa tu nombre',
          '#required' => TRUE,
          '#default_value' => '',
          '#prefix' => '<div class="name">',
          '#suffix' => '<span class="participant_'.$i.'_name-valid-message"></span></div>'
        ];
        $form['container']['participant_'.$i]['last_name'] = [
          '#type' => 'textfield',
          '#title' => $this->t('Apellido'),
          '#placeholder' => 'Ingresa tu apellido',
          '#required' => TRUE,
          '#default_value' => '',
          '#prefix' => '<div class="last_name">',
          '#suffix' => '<span class="participant_'.$i.'_last_name-valid-message"></span></div>'
        ];
        $form['container']['participant_'.$i]['birthday'] = [
          '#type' => 'select',
          '#title' => '',
          '#options' => $this->commonFunction->daysList(),
          '#empty_option' => $this->t(' DD '),
          '#required' => TRUE,
          '#default_value' => '',
          '#prefix' => '<div class="birthday">',
          '#suffix' => '<span class="participant_'.$i.'_birthday-valid-message"></span></div>'
        ];
        $form['container']['participant_'.$i]['birthmonth'] = [
          '#type' => 'select',
          '#title' => '',
          '#options' => $this->commonFunction->monthsList(),
          '#empty_option' => $this->t(' MM '),
          '#required' => TRUE,
          '#default_value' => '',
          '#prefix' => '<div class="birthmonth">',
          '#suffix' => '<span class="participant_'.$i.'_birthmonth-valid-message"></span></div>'
        ];
        $form['container']['participant_'.$i]['birthyear'] = [
          '#type' => 'select',
          '#title' => '',
          '#options' => $this->commonFunction->yearsList(),
          '#empty_option' => $this->t(' YYYY '),
          '#required' => TRUE,
          '#default_value' => '',
          '#prefix' => '<div class="birthyear">',
          '#suffix' => '<span class="participant_'.$i.'birthyear-valid-message"></span></div>'
        ];
        $form['container']['participant_'.$i]['dpi'] = [
          '#type' => 'textfield',
          '#title' => $this->t('DPI'),
          '#placeholder' => 'Ingresa tu DPI',
          '#required' => TRUE,
          '#default_value' => '',
          '#prefix' => '<div class="dpi">',
          '#suffix' => '<span class="participant_'.$i.'_dpi-valid-message"></span></div>'
        ];
        $form['container']['participant_'.$i]['email'] = [
          '#type' => 'email',
          '#title' => $this->t('Correo electrónico'),
          '#placeholder' => 'Ingresa tu Correo electrónico',
          '#required' => TRUE,
          '#default_value' => '',
          '#prefix' => '<div class="email">',
          '#suffix' => '<span class="participant_'.$i.'_email-valid-message"></span></div>'
        ];
        $form['container']['participant_'.$i]['phone'] = [
          '#type' => 'textfield',
          '#title' => $this->t('Teléfono'),
          '#placeholder' => '0000 0000 00',
          '#required' => TRUE,
          '#default_value' => '',
          '#prefix' => '<div class="phone">',
          '#suffix' => '<span class="participant_'.$i.'_phone-valid-message"></span></div>'
        ];
        $form['container']['participant_'.$i]['weight'] = [
          '#type' => 'select',
          '#title' => $this->t('Peso (LBs)'),
          '#options' => $this->commonFunction->weightsList(),
          '#empty_option' => $this->t('Selecciona una'),
          '#required' => TRUE,
          '#default_value' => '',
          '#prefix' => '<div class="weight">',
          '#suffix' => '<span class="participant_'.$i.'_weight-valid-message"></span></div>'
        ];
        $form['container']['participant_'.$i]['gender'] = [
          '#type' => 'select',
          '#title' => $this->t('Género'),
          '#options' => $this->commonFunction->gendersList(),
          '#empty_option' => $this->t('Selecciona uno'),
          '#required' => TRUE,
          '#default_value' => '',
          '#prefix' => '<div class="gender">',
          '#suffix' => '<span class="participant_'.$i.'_gender-valid-message"></span></div>'
        ];
        $form['container']['participant_'.$i]['city'] = [
          '#type' => 'select',
          '#title' => $this->t('Departamento'),
          '#options' => $this->commonFunction->citysList(),
          '#empty_option' => $this->t('Selecciona uno'),
          '#required' => TRUE,
          '#default_value' => '',
          '#prefix' => '<div class="city">',
          '#suffix' => '<span class="participant_'.$i.'_city-valid-message"></span></div>'
        ];
      }else {
        $label = 'Datos '.$part.' Participante';
        $form['container']['participant_'.$i] = array(
          '#type' => 'fieldset',
          '#title' => t($label),
        );
        $form['container']['participant_'.$i]['full_name'] = [
          '#type' => 'textfield',
          '#title' => $this->t('Nombre Completo'),
          '#placeholder' => 'Ingresa tu apellido',
          '#required' => TRUE,
          '#default_value' => '',
          '#prefix' => '<div class="full_name">',
          '#suffix' => '<span class="participant_'.$i.'_full_name-valid-message"></span></div>'
        ];
        $form['container']['participant_'.$i]['birthday'] = [
          '#type' => 'select',
          '#title' => '',
          '#options' => $this->commonFunction->daysList(),
          '#empty_option' => $this->t(' DD '),
          '#required' => TRUE,
          '#default_value' => '',
          '#prefix' => '<div class="birthday">',
          '#suffix' => '<span class="participant_'.$i.'_birthday-valid-message"></span></div>'
        ];
        $form['container']['participant_'.$i]['birthmonth'] = [
          '#type' => 'select',
          '#title' => '',
          '#options' => $this->commonFunction->monthsList(),
          '#empty_option' => $this->t(' MM '),
          '#required' => TRUE,
          '#default_value' => '',
          '#prefix' => '<div class="birthmonth">',
          '#suffix' => '<span class="participant_'.$i.'_birthmonth-valid-message"></span></div>'
        ];
        $form['container']['participant_'.$i]['birthyear'] = [
          '#type' => 'select',
          '#title' => '',
          '#options' => $this->commonFunction->yearsList(),
          '#empty_option' => $this->t(' YYYY '),
          '#required' => TRUE,
          '#default_value' => '',
          '#prefix' => '<div class="birthyear">',
          '#suffix' => '<span class="participant_'.$i.'_birthyear-valid-message"></span></div>'
        ];
        $form['container']['participant_'.$i]['dpi'] = [
          '#type' => 'textfield',
          '#title' => $this->t('DPI'),
          '#placeholder' => 'Ingresa tu DPI',
          '#required' => TRUE,
          '#default_value' => '',
          '#ajax' => [
            'event' => 'change',
            'callback' => '::validateDPICallback',
          ],
          '#prefix' => '<div class="dpi">',
          '#suffix' => '<span class="participant_'.$i.'_dpi-valid-message"></span></div>'
        ];
        $form['container']['participant_'.$i]['weight'] = [
          '#type' => 'select',
          '#title' => $this->t('Peso (LBs)'),
          '#options' => $this->commonFunction->weightsList(),
          '#empty_option' => $this->t('Selecciona una'),
          '#required' => TRUE,
          '#default_value' => '',
          '#prefix' => '<div class="weight">',
          '#suffix' => '<span class="participant_'.$i.'_weight-valid-message"></span></div>'
        ];
        $form['container']['participant_'.$i]['gender'] = [
          '#type' => 'select',
          '#title' => $this->t('Género'),
          '#options' => $this->commonFunction->gendersList(),
          '#empty_option' => $this->t('Selecciona uno'),
          '#required' => TRUE,
          '#default_value' => '',
          '#prefix' => '<div class="gender">',
          '#suffix' => '<span class="participant_'.$i.'_gender-valid-message"></span></div>'
        ];
      }
    }

    $form_state->setCached(FALSE);
    return $form;
  }

  public function widgetFlightCallback(array &$form, FormStateInterface $form_state) {
    $flight = $form_state->getValue('container')['widget']['flight'];
    $date = isset($form_state->getValue('container')['widget']['date']) ? $form_state->getValue('container')['widget']['date'] : '';
    $participants = isset($form_state->getValue('container')['widget']['participants']) ? intval($form_state->getValue('container')['widget']['participants']) : 1;

    $html =  $this->commonFunction->purchaseSummary($flight, $date, $participants);
    $form['container']['summary']['html']['#markup'] = $html;

    return $form['container'];
  }

  public function setMessage(array $form, FormStateInterface $form_state) {
    $response = new AjaxResponse();

    $webform_id = 'reserve_globo_modelo';
    $value = $form_state->getValue('container')['widget']['date'];
    $participants = $form_state->getValue('container')['widget']['participants'];
    if (!$value || empty($value)) {
      $validate = false;
      $response->addCommand(new HtmlCommand('.date-valid-message', 'Debes seleccionar una fecha'));
      $response->addCommand(new InvokeCommand('.date-valid-message', 'css', ['color', 'red']));
    }else {
      $validate = true;
      $response->addCommand(new ReplaceCommand('.date-valid-message', null));
    }
    $results = $this->commonFunction->queryAvailabilityFlightsWebForm($webform_id, $value, $participants);
    if (!$results) {
      $response->addCommand(new HtmlCommand('.date-valid-message', 'No hay disponibilidad'));
      $response->addCommand(new InvokeCommand('.date-valid-message', 'css', ['color', 'red']));
    }

    $value = $form_state->getValue('container')['summary']['terms_of_service'];dpm($value);
    if (!$value || empty($value)) {
      $validate = false;
      $response->addCommand(new HtmlCommand('.terms_of_service-valid-message', 'Debes aceptar términos y condiciones para continuar'));
      $response->addCommand(new InvokeCommand('.terms_of_service-valid-message', 'css', ['color', 'red']));
    }else {
      $validate = true;
      $response->addCommand(new ReplaceCommand('.terms_of_service-valid-message', null));
    }

    $regexpnum = '/^[a-zA-Z]{1,}/';
    $value = $form_state->getValue('container')['participant_0']['name'];
    if (!$value || empty($value)) {
      $validate = false;
      $response->addCommand(new HtmlCommand('.participant_0_name-valid-message', 'Ingresa tu nombre'));
      $response->addCommand(new InvokeCommand('.participant_0_name-valid-message', 'css', ['color', 'red']));
    }else if (preg_match($regexpnum, $value)) {
      $validate = true;
      $response->addCommand(new ReplaceCommand('.participant_0_name-valid-message', null));
    } else {
      $validate = false;
      $response->addCommand(new HtmlCommand('.participant_0_name-valid-message', 'No se permiten números o caracteres especiales'));
      $response->addCommand(new InvokeCommand('.participant_0_name-valid-message', 'css', ['color', 'red']));      
    }

    $regexpnum = '/^[a-zA-Z]{1,}/';
    $value = $form_state->getValue('container')['participant_0']['last_name'];
    if (!$value || empty($value)) {
      $validate = false;
      $response->addCommand(new HtmlCommand('.participant_0_last_name-valid-message', 'Ingresa tu apellido'));
      $response->addCommand(new InvokeCommand('.participant_0_last_name-valid-message', 'css', ['color', 'red']));
    }else if (preg_match($regexpnum, $value)) {
      $validate = true;
      $response->addCommand(new ReplaceCommand('.participant_0_last_name-valid-message', null));
    } else {
      $validate = false;
      $response->addCommand(new HtmlCommand('.participant_0_last_name-valid-message', 'No se permiten números o caracteres especiales'));
      $response->addCommand(new InvokeCommand('.participant_0_last_name-valid-message', 'css', ['color', 'red']));      
    }

    foreach ($form_state->getValues()['container'] as $kc => $c) {
      if( strpos($kc, 'participant_') === false){ }else {
        $value = $form_state->getValue('container')[$kc]['full_name'];  
        $regexpnum = '/^[a-zA-Z]{1,}/';
        if (!$value || empty($value)) {
          $validate = false;
          $response->addCommand(new HtmlCommand('.'.$kc.'_full_name-valid-message', 'Ingresa el nombre completo'));
          $response->addCommand(new InvokeCommand('.'.$kc.'_full_name-valid-message', 'css', ['color', 'red']));
        }else if (preg_match($regexpnum, $value)) {
          $validate = true;
          $response->addCommand(new ReplaceCommand('.'.$kc.'_full_name-valid-message', null));
        } else {
          $validate = false;
          $response->addCommand(new HtmlCommand('.'.$kc.'_full_name-valid-message', 'No se permiten números o caracteres especiales'));
          $response->addCommand(new InvokeCommand('.'.$kc.'_full_name-valid-message', 'css', ['color', 'red']));      
        }
      }
    }

    foreach ($form_state->getValues()['container'] as $kc => $c) {
      if( strpos($kc, 'participant_') === false){ }else {
        $birthday = $form_state->getValue('container')[$kc]['birthday'];
        $birthmonth = $form_state->getValue('container')[$kc]['birthmonth'];
        $birthyear = $form_state->getValue('container')[$kc]['birthyear'];
        if (!$birthday || empty($birthday)) {
          $validate = false;
          $response->addCommand(new HtmlCommand('.'.$kc.'_birthyear-valid-message', 'Ingresa tu fecha de nacimiento'));
          $response->addCommand(new InvokeCommand('.'.$kc.'_birthyear-valid-message', 'css', ['color', 'red']));
        }else if (!$birthmonth || empty($birthmonth)) {
          $validate = false;
          $response->addCommand(new HtmlCommand('.'.$kc.'_birthyear-valid-message', 'Ingresa tu fecha de nacimiento'));
          $response->addCommand(new InvokeCommand('.'.$kc.'_birthyear-valid-message', 'css', ['color', 'red']));
        }else if (!$birthyear || empty($birthyear)) {
          $validate = false;
          $response->addCommand(new HtmlCommand('.'.$kc.'_birthyear-valid-message', 'Ingresa tu fecha de nacimiento'));
          $response->addCommand(new InvokeCommand('.'.$kc.'_birthyear-valid-message', 'css', ['color', 'red']));
        }else if(!empty($birthyear) && !empty($birthmonth) && !empty($birthday)) {
          $birthday = $birthyear . '-' . $birthmonth . '-' . $birthday;
          $today = new \DateTime(date("Y-m-d"));
          $bday = new \DateTime($birthday);
          $interval = $today->diff($bday);
          if(intval($interval->y) < 18){
            $validate = false;
            $response->addCommand(new HtmlCommand('.'.$kc.'_birthyear-valid-message', 'Para registrarte debes ser mayor de edad'));
            $response->addCommand(new InvokeCommand('.'.$kc.'_birthyear-valid-message', 'css', ['color', 'red']));
          }
        }else {
          $validate = true;
          $response->addCommand(new ReplaceCommand('.'.$kc.'_birthyear-valid-message', null));
        }
      }
    }

    foreach ($form_state->getValues()['container'] as $kc => $c) {
      if( strpos($kc, 'participant_') === false){ }else {
        $value = $form_state->getValue('container')[$kc]['dpi'];  
        $expr = '/^[0-9]{1,}/';
        if (!$value || empty($value)) {
          $validate = false;
          $response->addCommand(new HtmlCommand('.'.$kc.'_dpi-valid-message', 'Ingresa un DPI válido'));
          $response->addCommand(new InvokeCommand('.'.$kc.'_dpi-valid-message', 'css', ['color', 'red']));
        }else if (preg_match($expr, $value)) {
          $validate = true;
          $response->addCommand(new ReplaceCommand('.'.$kc.'_dpi-valid-message', null));
        } else {
          $validate = false;
          $response->addCommand(new HtmlCommand('.'.$kc.'_dpi-valid-message', 'No se permiten letras o caracteres especiales'));
          $response->addCommand(new InvokeCommand('.'.$kc.'_dpi-valid-message', 'css', ['color', 'red']));      
        }
      }
    }

    foreach ($form_state->getValues()['container'] as $kc => $c) {
      if( strpos($kc, 'participant_') === false){ }else {
        $value = $form_state->getValue('container')[$kc]['email'];  
        if (!$value || empty($value)) {
          $validate = false;
          $response->addCommand(new HtmlCommand('.'.$kc.'_email-valid-message', 'Ingresa tu correo electrónico'));
          $response->addCommand(new InvokeCommand('.'.$kc.'_email-valid-message', 'css', ['color', 'red']));
        }elseif (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
          $validate = false;
          $response->addCommand(new HtmlCommand('.'.$kc.'_email-valid-message', 'Ingresa un correo electrónico válido'));
          $response->addCommand(new InvokeCommand('.'.$kc.'_email-valid-message', 'css', ['color', 'red']));
        }else {
          $validate = true;
          $response->addCommand(new ReplaceCommand('.'.$kc.'_email-valid-message', null));
        }
      }
    }

    foreach ($form_state->getValues()['container'] as $kc => $c) {
      if( strpos($kc, 'participant_') === false){ }else {
        $value = $form_state->getValue('container')[$kc]['phone'];  
        $expr = '/^[0-9]{1,}/';
        if (!$value || empty($value)) {
          $validate = false;
          $response->addCommand(new HtmlCommand('.'.$kc.'_phone-valid-message', 'Ingresa tu número de télefono'));
          $response->addCommand(new InvokeCommand('.'.$kc.'_phone-valid-message', 'css', ['color', 'red']));
        }elseif (preg_match($expr, $phone) && filter_var($phone, FILTER_VALIDATE_INT)) {
          $validate = false;
          $response->addCommand(new HtmlCommand('.'.$kc.'_phone-valid-message', 'Ingresa un número de télefono válido'));
          $response->addCommand(new InvokeCommand('.'.$kc.'_phone-valid-message', 'css', ['color', 'red']));
        }else {
          $validate = true;
          $response->addCommand(new ReplaceCommand('.'.$kc.'_phone-valid-message', null));
        }
      }
    }

    foreach ($form_state->getValues()['container'] as $kc => $c) {
      if( strpos($kc, 'participant_') === false){ }else {
        $value = $form_state->getValue('container')[$kc]['weight'];  
        $expr = '/^[0-9]{1,}/';
        if (!$value || empty($value)) {
          $validate = false;
          $response->addCommand(new HtmlCommand('.'.$kc.'_weight-valid-message', 'Debes seleccionar una opción'));
          $response->addCommand(new InvokeCommand('.'.$kc.'_weight-valid-message', 'css', ['color', 'red']));
        }else {
          $validate = true;
          $response->addCommand(new ReplaceCommand('.'.$kc.'_weight-valid-message', null));
        }
      }
    }

    foreach ($form_state->getValues()['container'] as $kc => $c) {
      if( strpos($kc, 'participant_') === false){ }else {
        $value = $form_state->getValue('container')[$kc]['gender'];  
        $expr = '/^[0-9]{1,}/';
        if (!$value || empty($value)) {
          $validate = false;
          $response->addCommand(new HtmlCommand('.'.$kc.'_gender-valid-message', 'Debes seleccionar una opción'));
          $response->addCommand(new InvokeCommand('.'.$kc.'_gender-valid-message', 'css', ['color', 'red']));
        }else {
          $validate = true;
          $response->addCommand(new ReplaceCommand('.'.$kc.'_gender-valid-message', null));
        }
      }
    }

    foreach ($form_state->getValues()['container'] as $kc => $c) {
      if( strpos($kc, 'participant_') === false){ }else {
        $value = $form_state->getValue('container')[$kc]['city'];  
        $expr = '/^[0-9]{1,}/';
        if (!$value || empty($value)) {
          $validate = false;
          $response->addCommand(new HtmlCommand('.'.$kc.'_city-valid-message', 'Debes seleccionar una opción'));
          $response->addCommand(new InvokeCommand('.'.$kc.'_city-valid-message', 'css', ['color', 'red']));
        }else {
          $validate = true;
          $response->addCommand(new ReplaceCommand('.'.$kc.'_city-valid-message', null));
        }
      }
    }
    
    if($validate) {
      $data_main = [];
      $token = $this->commonFunction->generateToken();
      $flight = $form_state->getValue('container')['widget']['flight'];
      $participants = isset($form_state->getValue('container')['widget']['participants']) ? intval($form_state->getValue('container')['widget']['participants']) : 0;
      $total = $this->commonFunction->calculatePrice($participants, $flight); 

      $flight = [
        'id_reserve' => $token,
        'state' => 'p',
        'type' => isset($form_state->getValue('container')['widget']['flight']) ? $form_state->getValue('container')['widget']['flight'] : '',
        'date' => isset($form_state->getValue('container')['widget']['date']) ? $form_state->getValue('container')['widget']['date'] : '',
        'participants' => $participants,
        'value' => $price,
        'total' => $total,
      ];
      $terms_of_service = '';
      $marketing = '';
      $webform_id = 'reserve_globo_modelo';
      $result = $this->commonFunction->saveDataWebForm($webform_id, $flight);

      $webform_id = 'reserve_data_globo_modelo';
      for ($i=0; $i < $participants; $i++) { 
        $part = 'participant_'.$i;
        if($i == 0){
          $terms_of_service = isset($form_state->getValue('container')['summary']['terms_of_service']) ? $form_state->getValue('container')['summary']['terms_of_service'] : '';
          $marketing = isset($form_state->getValue('container')['summary']['marketing']) ? $form_state->getValue('container')['summary']['marketing'] : '';

          $full_name = $form_state->getValue('container')[$part]['name'] .' '. $form_state->getValue('container')[$part]['last_name'];

        }else {
          $terms_of_service = '';
          $marketing = '';
          $full_name = isset($form_state->getValue('container')[$part]['full_name']) ? $form_state->getValue('container')[$part]['full_name'] : '';
        }
        $data = [
          'id_reserve' => $token,
          'name' => isset($form_state->getValue('container')[$part]['name']) ? $form_state->getValue('container')[$part]['name'] : '',
          'last_name' => isset($form_state->getValue('container')[$part]['last_name']) ? $form_state->getValue('container')[$part]['last_name'] : '',
          'full_name' => $full_name,
          'birthday' => isset($form_state->getValue('container')[$part]['birthday']) ? $form_state->getValue('container')[$part]['birthday'] : '',
          'birthmonth' => isset($form_state->getValue('container')[$part]['birthmonth']) ? $form_state->getValue('container')[$part]['birthmonth'] : '',
          'birthyear' => isset($form_state->getValue('container')[$part]['birthyear']) ? $form_state->getValue('container')[$part]['birthyear'] : '',
          'dpi' => isset($form_state->getValue('container')[$part]['dpi']) ? $form_state->getValue('container')[$part]['dpi'] : '',
          'email' => isset($form_state->getValue('container')[$part]['email']) ? $form_state->getValue('container')[$part]['email'] : '',
          'phone' => isset($form_state->getValue('container')[$part]['phone']) ? $form_state->getValue('container')[$part]['phone'] : '',
          'weight' => isset($form_state->getValue('container')[$part]['weight']) ? $form_state->getValue('container')[$part]['weight'] : '',
          'gender' => isset($form_state->getValue('container')[$part]['gender']) ? $form_state->getValue('container')[$part]['gender'] : '',
          'city' => isset($form_state->getValue('container')[$part]['city']) ? $form_state->getValue('container')[$part]['city'] : '',
          'terms_of_service' => $terms_of_service,
          'marketing' => $marketing,
        ];
        $data_main[] = $data;
        $result = $this->commonFunction->saveDataWebForm($webform_id, $data);

      }
      $response = new AjaxResponse();
      $response->addCommand(
        new HtmlCommand(
          '#ajax-response-container',
          '<div class="message-response">
            <div class="title">Hemos registrado tu reserva de forma exitosa</div><br><br>
            <p>Hemos enviado la información de reserva a:</p><br>
            <span>'.$data_main[0]['email'].'</span><br>
            <p>Recuerda que tu código de reserva es:</p><br>
            <div class="code">'.$data_main[0]['id_reserve'].'</div><br><br>
            <a href="/reservas" class="button">Regresar al home</a>
            </div>'),
      );
    }
    return $response;
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    //return parent::submitForm($form, $form_state);
  }
}
