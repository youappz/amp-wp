<?php
/**
 * Single cron event to validate a saved post's permalink in the background.
 *
 * @package AMP
 * @since 2.1
 */

namespace AmpProject\AmpWP\Validation;

use AmpProject\AmpWP\BackgroundTask\BackgroundTaskDeactivator;
use AmpProject\AmpWP\BackgroundTask\SingleScheduledBackgroundTask;
use AmpProject\AmpWP\DevTools\UserAccess;

/**
 * SavePostValidationEvent class.
 *
 * @since 2.1
 *
 * @internal
 */
final class SavePostValidationEvent extends SingleScheduledBackgroundTask {

	/**
	 * Instance of URLValidationProvider
	 *
	 * @var URLValidationProvider
	 */
	private $url_validation_provider;

	/**
	 * Instance of UserAccess.
	 *
	 * @var UserAccess
	 */
	private $dev_tools_user_access;

	/**
	 * The cron action name.
	 *
	 * @var string
	 */
	const BACKGROUND_TASK_NAME = 'amp_single_post_validate';

	/**
	 * Class constructor.
	 *
	 * @param BackgroundTaskDeactivator $background_task_deactivator Background task deactivator instance.
	 * @param UserAccess                $dev_tools_user_access Dev tools user access class instance.
	 */
	public function __construct( BackgroundTaskDeactivator $background_task_deactivator, UserAccess $dev_tools_user_access ) {
		parent::__construct( $background_task_deactivator );

		$this->dev_tools_user_access = $dev_tools_user_access;
	}

	/**
	 * Callback for the cron action.
	 *
	 * @param mixed ...$args The args received with the action hook where the event was scheduled.
	 */
	public function process( ...$args ) {
		$post_id = reset( $args );

		if ( empty( get_post( $post_id ) ) ) {
			return;
		}

		$this->get_url_validation_provider()->get_url_validation(
			get_the_permalink( $post_id ),
			get_post_type( $post_id ),
			true
		);
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
	 * Returns whether the event should be scheduled.
	 *
	 * @param array $args Args passed from the action hook where the event is scheduled.
	 * @return boolean
	 */
	protected function should_schedule_event( $args ) {
		// Validation is performed on post save if user has dev tools on.
		if ( $this->dev_tools_user_access->is_user_enabled( wp_get_current_user() ) ) {
			return false;
		}

		if ( ! is_array( $args ) || count( $args ) !== 1 ) {
			return false;
		}

		$id = reset( $args );

		if ( wp_is_post_revision( $id ) ) {
			return false;
		}

		if ( ! amp_is_post_supported( $id ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Gets the hook on which to schedule the event.
	 *
	 * @return string The action hook name.
	 */
	protected function get_action_hook() {
		return 'save_post';
	}

	/**
	 * Provides the URLValidationProvider instance, creating it if needed.
	 *
	 * @return URLValidationProvider
	 */
	private function get_url_validation_provider() {
		if ( is_null( $this->url_validation_provider ) ) {
			$this->url_validation_provider = new URLValidationProvider();
		}

		return $this->url_validation_provider;
	}
}
