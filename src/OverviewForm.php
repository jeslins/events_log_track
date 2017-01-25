<?php

namespace Drupal\event_log_track;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\Html;
use Drupal\Core\Url;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;

/**
 * Configure user settings for this site.
 */
class OverviewForm extends FormBase {

    function getFormId() {
        return 'event_log_track_filter';
    }

    function buildForm(array $form, FormStateInterface $form_state) {
        $form['filters'] = array(
            '#type' => 'details',
            '#title' => $this->t('Filters'),
            '#description' => t('Filter the events.'),
            '#open' => FALSE,
        );

        $handlers = event_log_track_get_event_handlers();
        $options = array();
        foreach ($handlers as $type => $handler) {
            $options[$type] = $handler['title'];
        }
        $form['filters']['type'] = array(
            '#type' => 'select',
            '#title' => t('Type'),
            '#description' => t('Event type'),
            '#options' => array('' => 'Select a type') + $options,
            '#ajax' => array(
                'callback' => '::event_log_track_overview_page_form_get_ajax_operations',
                'event' => 'change',
            ),
        );

        $form['filters']['operation'] = EventLogStorage::event_log_track_overview_page_form_get_operations(empty($form_state->getUserInput()['type']) ? '' : $form_state->getUserInput()['type']);

        $form['filters']['user'] = array(
            '#type' => 'entity_autocomplete',
            '#target_type' => 'user',
            '#selection_settings' => ['include_anonymous' => FALSE],
            '#title' => t('User'),
            '#description' => t('The user that triggered this event.'),
            '#size' => 30,
            '#maxlength' => 60,
        );

        $form['filters']['id'] = array(
            '#type' => 'textfield',
            '#size' => 5,
            '#title' => t('ID'),
            '#prefix' => '<br />',
            '#description' => t('The id of the subject (numeric).'),
        );

        $form['filters']['ip'] = array(
            '#type' => 'textfield',
            '#size' => 20,
            '#title' => t('IP'),
            '#prefix' => '<br />',
            '#description' => t('The ip address of the visitor.'),
        );

        $form['filters']['name'] = array(
            '#type' => 'textfield',
            '#size' => 10,
            '#title' => t('Name'),
            '#description' => t('The (machine) name of the subject.'),
        );

        $form['filters']['path'] = array(
            '#type' => 'textfield',
            '#size' => 30,
            '#title' => t('Path'),
            '#description' => t('The full path.'),
        );

        $form['filters']['keyword'] = array(
            '#type' => 'textfield',
            '#size' => 10,
            '#title' => t('Keyword'),
            '#description' => t('Search in the description.'),
        );

        $form['filters']['submit'] = array(
            '#type' => 'submit',
            '#value' => $this->t('Submit'),
        );

        $header = array(
            array('data' => t('Updated'), 'field' => 'created', 'sort' => 'desc'),
            array('data' => t('Type'), 'field' => 'type'),
            array('data' => t('Operation'), 'field' => 'operation'),
            array('data' => t('Path'), 'field' => 'path'),
            array('data' => t('Description'), 'field' => 'description'),
            array('data' => t('User'), 'field' => 'uid'),
            array('data' => t('IP'), 'field' => 'ip'),
            array('data' => t('ID'), 'field' => 'ref_numeric'),
            array('data' => t('Name'), 'field' => 'ref_char'),
        );

        $formData = (!empty($form_state->getUserInput())) ? $form_state->getUserInput() : array();
        $limit = 20;
        $result = EventLogStorage::getSearchData($formData, $header, $limit);

        $rows = array();
        foreach ($result as $record) {
            if (!empty($record->uid)) {
                $account = \Drupal\user\Entity\User::load($record->uid);
                $userLink = \Drupal::l($account->getUsername(), Url::fromUri('internal:/user/' . $account->id()));
            } else {
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
            '#empty' => t('No events found.'),
        );

        // Finally add the pager.
        $build['pager'] = array(
            '#type' => 'pager'
        );
        $form['results'] = $build;

        return $form;
    }

    function submitForm(array &$form, FormStateInterface $form_state) {
        $form_state->disableRedirect();
        $form_state->setRebuild();
    }

    /**
     * Ajax callback for the operations options.
     */
    function event_log_track_overview_page_form_get_ajax_operations(array &$form, FormStateInterface $form_state) {
        $ajax_response = new AjaxResponse();

        $element = EventLogStorage::event_log_track_overview_page_form_get_operations($form_state->getValue('type'));
        $ajax_response->addCommand(new HtmlCommand('#operation-dropdown-replace', $element));

        return $ajax_response;
    }

}
