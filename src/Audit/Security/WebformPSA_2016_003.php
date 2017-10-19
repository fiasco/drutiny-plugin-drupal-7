<?php

namespace Drutiny\Plugin\Drupal7\Audit\Security;

use Drutiny\Audit\Drupal\ModuleEnabled;
use Drutiny\Sandbox\Sandbox;

/**
 * @Drutiny\Annotation\CheckInfo(
 *  title = "Webform upload",
 *  description = "Spammers are known to want to uplaod files to webforms that allow anonymous user users access.",
 *  remediation = "Restrict upload types, enforce a max upload size, use a random folder underneath <code>/webform/</code> to store the uploads.",
 *  not_available = "Webform is not enabled.",
 *  success = "There are no files uploaded that look malicious.",
 *  failure = "There :prefix <code>:number_of_silly_uploads</code> malicious webform upload:plural.:files",
 *  exception = "Could not determine the amount of malicious uploads.",
 * )
 */
class WebformPSA_2016_003 extends ModuleEnabled {

  /**
   *
   */
  public function audit(Sandbox $sandbox) {
    // Use the audit from ModuleEnable to validate check.
    $sandbox->setParameter('module', 'webform');
    if (!parent::audit($sandbox)) {
      return NULL;
    }

    // Look for NFL uploads.
    // See https://www.drupal.org/forum/newsletters/security-public-service-announcements/2016-10-10/drupal-file-upload-by-anonymous
    $output = $sandbox->drush()->sqlq("SELECT filename FROM {file_managed} WHERE UPPER(filename) LIKE '%NFL%' AND status = 0;");
    $output = array_filter($output);
    if (empty($output)) {
      $number_of_silly_uploads = 0;
      $sandbox->setParameter('files', '');
    }
    else {
      $number_of_silly_uploads = count($output);

      // Format with markdown code backticks.
      $output = array_map(function ($filepath) {
        return "`$filepath`";
      }, $output);

      $sandbox->setParameter('files', '- ' . implode("\n- ", $output) . '</code>');
    }
    $sandbox->setParameter('number_of_silly_uploads', $number_of_silly_uploads);
    $sandbox->setParameter('plural', $number_of_silly_uploads > 1 ? 's' : '');
    $$sandbox->setParameter('prefix', $number_of_silly_uploads > 1 ? 'are' : 'is');

    return $number_of_silly_uploads === 0;
  }

}
