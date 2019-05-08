<?php

// drupal_get_path calls drupal_get_filename which invokes \Drupal::service("extension.list.$type");
// this will cause the bootstrap process to always die.
drupal_get_path('module', 'contained_not_initialized');
