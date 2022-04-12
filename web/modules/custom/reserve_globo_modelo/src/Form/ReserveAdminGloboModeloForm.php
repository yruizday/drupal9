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
 * Declaration of class ReserveAdminGloboModeloForm.
 */
class ReserveAdminGloboModeloForm extends FormBase {

  protected $commonFunction;
  public $types_flights;
  public $states_flights;

  public function __construct(ReserveGloboModeloManagerInterface $interface) {
    $this->commonFunction = $interface;
    $this->types_flights = $this->commonFunction->typesFlights();
    $this->states_flights = $this->commonFunction->statesFlights();
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('reserve_globo_modelo.manager')
    );
  }

  public function getFormId() {
    return 'reserve_check_globo_modelo_form';
  }

  protected function getEditableConfigNames() {
    return [
      'reserve_globo_modelo.settings',
    ];
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['#tree'] = TRUE;  

    $form['container'] = [
        '#type' => 'container',
        '#attributes' => ['id' => 'ajax-response-container'],
    ];
    $form['container']['html'] = [
      '#type' => 'markup',
      '#markup' => '<h2> Ingresa tú código de reserva </h2>',
    ];
    $form['container']['id_reserve'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Buscar'),
      '#default_value' => 'yXoVtL9i',
      '#suffix' => '<span class="query-valid-message"></span>'
    ];
    $form['container']['date'] = [
      '#type' => 'date',
      '#title' => $this->t('Fecha'),
      '#default_value' => '',
      '#ajax' => [
        'callback' => '::widgetFlightDateCallback',
        'wrapper' => 'ajax-response-container',
        'event' => 'change',
      ],
      '#weight' => 0,
      '#suffix' => '<span class="date-valid-message"></span>'
    ];
    $form['container']['state'] = [
      '#type' => 'select',
      '#title' => $this->t('Estado'),
      '#options' => $this->commonFunction->statesFlights(),
      '#default_value' => '',
      '#required' => false,
      '#suffix' => '<span class="state-valid-message"></span>'
    ];

    $form['container']['actions']['#type'] = 'actions';
    $form['container']['actions']['submit'] = [
     '#type' => 'submit',
     '#value' => $this->t('Consultar'),
      '#ajax' => [
        'callback' => '::setMessage'
      ],
    ];

    $form_state->setCached(FALSE);
    return $form;
  }


  public function setMessage(array $form, FormStateInterface $form_state) {    
    $response = new AjaxResponse();
    $webform_id = 'reserve_globo_modelo';
    $query = $form_state->getValue('container')['query'];
    $results = $this->commonFunction->queryDataWebForm($webform_id, $query);
    if(!empty($results)){
      foreach ($results as $key => $value) {
        $html = '
        <h2>Codigo de reserva: '.$value['id_reserve'].'</h2><br>
        Estado de la reserva: '.$this->states_flights[$value['state']].' <br>
        '.$$value['date'].'<br>
        Direccion: XXXXXXX <br>
        Tipo de vuelo: '.$this->types_flights[$value['type']].'<br>
        Número de participantes: '.$value['participants'].'<br><br><br>';
      }

      $webform_id = 'reserve_data_globo_modelo';
      $results = $this->commonFunction->queryDataWebForm($webform_id, $query);
      foreach ($results as $key => $value) {
        $part = $key+1;
        $label = 'Datos '.$part.' Participante';
        $html = $html.'
        '.$label.' <br>
        Nombres: '.$value['full_name'].' <br>
        Fecha de nacimiento: '.$value['birthday'].' <br>
        DPI: '.$value['dpi'].' <br>
        Peso: '.$value['weight'].' <br>
        Genero: '.$value['gender'].' <br><br><br>';
      }
      $response->addCommand(new HtmlCommand('#ajax-response-container', $html));
    }else {
      $response->addCommand(new HtmlCommand('.query-valid-message', 'Lo sentimos, No tenemos Resultados para tu búsqueda'));
    }
    return $response;
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
   //return parent::submitForm($form, $form_state);
  }
}
