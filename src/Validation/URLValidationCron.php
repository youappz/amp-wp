<?php
/**
 * WP cron process to validate URLs in the background.
 *
 * @package AMP
 * @since   2.1
 */

namespace AmpProject\AmpWP\Validation;

use AmpProject\AmpWP\BackgroundTask\BackgroundTaskDeactivator;
use AmpProject\AmpWP\BackgroundTask\RecurringBackgroundTask;
use AmpProject\AmpWP\Infrastructure\Conditional;

/**
 * URLValidationCron class.
 *
 * @since 2.1
 *
 * @internal
 */
final class URLValidationCron extends RecurringBackgroundTask {

	/**
	 * URLValidationProvider instance.
	 *
	 * @var URLValidationProvider
	 */
	private $url_validation_provider;

	/**
	 * The cron action name.
	 *
	 * @var string
	 */
	const BACKGROUND_TASK_NAME = 'amp_validate_urls';

	/**
	 * Class constructor.
	 *
	 * @param BackgroundTaskDeactivator $background_task_deactivator Service that deactivates background events.
	 * @param URLValidationProvider     $url_validation_provider     URLValidationProvider instance.
	 */
	public function __construct( BackgroundTaskDeactivator $background_task_deactivator, URLValidationProvider $url_validation_provider ) {
		parent::__construct( $background_task_deactivator );

		$this->url_validation_provider = $url_validation_provider;
	}

	/**
	 * Callback for the cron action.
	 *
	 * @param mixed[] ...$args Unused callback arguments.
	 */
	public function process( ...$args ) { // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
		$validation_queue_key = 'amp_url_validation_queue';
		$validation_queue     = get_option( $validation_queue_key, [] );

		if ( empty( $validation_queue ) || ! is_array( $validation_queue ) ) {
			return [];
		}

		$limit = 5;
		$count = 1;

		foreach ( $validation_queue as $hash => $url ) {
			if ( empty( $url['url'] ) || empty( $url['type'] ) ) {
				continue;
			}

			$response = $this->url_validation_provider->get_url_validation( $url['url'], $url['type'] );
			if ( empty( $response ) || is_wp_error( $response ) ) {
				continue;
			}

			unset( $validation_queue[ $hash ] );
			$count ++;
			if ( $limit < $count ) {
				break;
			}
		}

		update_option( $validation_queue_key, $validation_queue );
	}

	/**
	 * Get the event name.
	 *
	 * This is the "slug" of the event, not the display name.
	 *
	 * Note: the event name should be prefixed to prevent naming collisions.
	 *
	 * @return string Name of the event.
	 */
	protected function get_event_name() {
		return self::BACKGROUND_TASK_NAME;
	}

	/**
	 * Get the interval to use for the event.
	 *
	 * @return string An existing interval name.
	 */
	protected function get_interval() {
		return self::DEFAULT_INTERVAL_EVERY_TEN_MINUTES;
	}
}
