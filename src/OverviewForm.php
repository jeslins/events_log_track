<?php

namespace Drupal\event_log_track;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\Html;
use Drupal\Core\Url;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use \Drupal\user\Entity\User;

/**
 * Configure user settings for this site.
 */
class OverviewForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'event_log_track_filter';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['filters'] = array(
      '#type' => 'details',
      '#title' => $this->t('Filters'),
      '#description' => $this->t('Filter the events.'),
      '#open' => FALSE,
    );

    $handlers = event_log_track_get_event_handlers();
    $options = array();
    foreach ($handlers as $type => $handler) {
      $options[$type] = $handler['title'];
    }
    $form['filters']['type'] = array(
      '#type' => 'select',
      '#title' => $this->t('Type'),
      '#description' => $this->t('Event type'),
      '#options' => array('' => 'Select a type') + $options,
      '#ajax' => array(
        'callback' => '::formGetAjaxOperation',
        'event' => 'change',
      ),
    );

    $form['filters']['operation'] = EventLogStorage::formGetOperations(empty($form_state->getUserInput()['type']) ? '' : $form_state->getUserInput()['type']);

    $form['filters']['user'] = array(
      '#type' => 'entity_autocomplete',
      '#target_type' => 'user',
      '#selection_settings' => ['include_anonymous' => FALSE],
      '#title' => $this->t('User'),
      '#description' => $this->t('The user that triggered this event.'),
      '#size' => 30,
      '#maxlength' => 60,
    );

    $form['filters']['id'] = array(
      '#type' => 'textfield',
      '#size' => 5,
      '#title' => $this->t('ID'),
      '#description' => $this->t('The id of the subject (numeric).'),
    );

    $form['filters']['ip'] = array(
      '#type' => 'textfield',
      '#size' => 20,
      '#title' => $this->t('IP'),
      '#description' => $this->t('The ip address of the visitor.'),
    );

    $form['filters']['name'] = array(
      '#type' => 'textfield',
      '#size' => 10,
      '#title' => $this->t('Name'),
      '#description' => $this->t('The (machine) name of the subject.'),
    );

    $form['filters']['path'] = array(
      '#type' => 'textfield',
      '#size' => 30,
      '#title' => $this->t('Path'),
      '#description' => $this->t('The full path.'),
    );

    $form['filters']['keyword'] = array(
      '#type' => 'textfield',
      '#size' => 10,
      '#title' => $this->t('Keyword'),
      '#description' => $this->t('Search in the description.'),
    );

    $form['filters']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    );

    $header = array(
        array(
          'data' => $this->t('Updated'),
          'field' => 'created',
          'sort' => 'desc',
        ),
        array('data' => $this->t('Type'), 'field' => 'type'),
        array('data' => $this->t('Operation'), 'field' => 'operation'),
        array('data' => $this->t('Path'), 'field' => 'path'),
        array('data' => $this->t('Description'), 'field' => 'description'),
        array('data' => $this->t('User'), 'field' => 'uid'),
        array('data' => $this->t('IP'), 'field' => 'ip'),
        array('data' => $this->t('ID'), 'field' => 'ref_numeric'),
        array('data' => $this->t('Name'), 'field' => 'ref_char'),
    );

    $formData = (!empty($form_state->getUserInput())) ? $form_state->getUserInput() : array();
    $limit = 20;
    $result = EventLogStorage::getSearchData($formData, $header, $limit);

    $rows = array();
    foreach ($result as $record) {
      if (!empty($record->uid)) {
        $account = User::load($record->uid);
        $userLink = \Drupal::l($account->getUsername(), Url::fromUri('internal:/user/' . $account->id()));
      }
      else {
        $account = NULL;
      }
      $rows[] = array(
          array('data' => Html::escape(date("Y-m-d H:i:s", $record->created))),
          array('data' => Html::escape($record->type)),
          array('data' => Html::escape($record->operation)),
          array('data' => Html::escape($record->path)),
          array('data' => strip_tags($record->description)),
          array('data' => (empty($account) ? '' : $userLink)),
          array('data' => Html::escape($record->ip)),
          array('data' => Html::escape($record->ref_numeric)),
          array('data' => Html::escape($record->ref_char)),
      );
    }

    // Generate the table.
    $build['config_table'] = array(
      '#theme' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#empty' => $this->t('No events found.'),
    );

    // Finally add the pager.
    $build['pager'] = array(
      '#type' => 'pager',
    );
    $form['results'] = $build;

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_state->disableRedirect();
    $form_state->setRebuild();
  }

  /**
   * Ajax callback for the operations options.
   */
  public function formGetAjaxOperation(array &$form, FormStateInterface $form_state) {
    $ajax_response = new AjaxResponse();

    $element = EventLogStorage::formGetOperations($form_state->getValue('type'));
    $ajax_response->addCommand(new HtmlCommand('#operation-dropdown-replace', $element));

    return $ajax_response;
  }

}
