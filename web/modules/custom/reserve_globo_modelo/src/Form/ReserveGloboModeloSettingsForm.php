<?php

namespace Drupal\reserve_globo_modelo\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Declaration of class ReserveGloboModeloSettingsForm.
 */
class ReserveGloboModeloSettingsForm extends ConfigFormBase {

  public function getFormId() {
    return 'reserve_globo_modelo_settings_form';
  }

  protected function getEditableConfigNames() {
    return [
      'reserve_globo_modelo.settings',
    ];
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('reserve_globo_modelo.settings');
    $form['c'] = array(
      '#type' => 'fieldset',
      '#title' => t('Vuelo Cautivo'),
    );
    $form['c']['price_c'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Precio del vuelo (Dolares)'),
      '#default_value' => $config->get('reserve_globo_modelo.flights')['c']['price'],
      '#required' => TRUE,
    ];
    $form['l'] = array(
      '#type' => 'fieldset',
      '#title' => t('Vuelo Libre'),
    );
    $form['l']['price_l'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Precio del vuelo (Dolares)'),
      '#default_value' => $config->get('reserve_globo_modelo.flights')['l']['price'],
      '#required' => TRUE,
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->configFactory->getEditable('reserve_globo_modelo.settings');

    $flights = [];
    $flights['c']['price'] = $form_state->getValue('price_c');
    $flights['l']['price'] = $form_state->getValue('price_l');

    $config->set('reserve_globo_modelo.flights', $flights);
    $config->save();
    $this->messenger()->addStatus($this->t('Configuración guardada.'));
  }

}
