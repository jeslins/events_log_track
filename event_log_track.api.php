<?php

/**
 * @file
 * Documentation for the Event Log module.
 */

/**
 * Returns event log handlers.
 * 
 * @return array
 *   An associative array, keyed by event type, and valued by handler info:
 *   Returns the event to be inserted in the event log track, if any.
 *
 * Optional. Notice that events can also be manually created using the
 * event_log_track_save function.
 */
function hook_event_log_track_handlers() {
  $handlers = array();

  return $handlers;
}
