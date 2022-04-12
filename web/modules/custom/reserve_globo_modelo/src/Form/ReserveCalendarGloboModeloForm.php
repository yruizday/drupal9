<?php

namespace Drupal\reserve_globo_modelo\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;

/**
 * Declaration of class ReserveCalendarGloboModeloForm.
 */
class ReserveCalendarGloboModeloForm extends FormBase {

  public function getFormId() {
    return 'reserve_calendar_globo_modelo_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['flight'] =[
      '#type' => 'radios',
      '#title' => t('Tipo de Vuelo'),
      '#options' => ['c' => 'Vuelo Cautivo', 'l' => 'Vuelo Libre'],
      '#required' => TRUE,
    ];
    for ($i=1; $i < 9; $i++) { 
      $p[$i] = $i;
    }
    $form['participants'] = [
      '#type' => 'select',
      '#title' => $this->t('Participantes'),
      '#options' => $p,
      '#default_value' => '1',
      '#required' => TRUE
    ];
    $now = DrupalDateTime::createFromTimestamp(time());
    $now->setTimezone(new \DateTimeZone('UTC'));
    $date = $now->format('d-m-Y');
    $form['date'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Fecha'),
      '#default_value' => $date
    ];
    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
     '#type' => 'submit',
     '#value' => $this->t('OK'),
    ];
    $form_state->setCached(FALSE);
    return $form;
  }


  public function submitForm(array &$form, FormStateInterface $form_state) {
    
    $redirect_params['f'] = $form_state->getValue('flight');
    $redirect_params['d'] = $form_state->getValue('date');
    $redirect_params['p'] = $form_state->getValue('participants');
    

    $url = Url::fromRoute('reserve_globo_modelo.reserve', $redirect_params);
    $form_state->setRedirectUrl($url);

    //return parent::submitForm($form, $form_state);
  }
}
