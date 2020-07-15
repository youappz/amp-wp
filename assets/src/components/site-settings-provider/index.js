/**
 * External dependencies
 */
import PropTypes from 'prop-types';

/**
 * WordPress dependencies
 */
import { createContext, useState, useEffect, useContext } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
/**
 * Internal dependencies
 */
import { useError } from '../../utils/use-error';
import { ErrorContext } from '../error-boundary';

export const SiteSettings = createContext();

/**
 * Site settings context provider.
 *
 * @param {Object} props Component props.
 * @param {any} props.children Component children.
 */
export function SiteSettingsProvider( { children } ) {
	const [ settings, setSettings ] = useState( {} );
	const [ fetchingSiteSettings, setFetchingSiteSettings ] = useState( false );

	const { setError } = useError();
	const error = useContext( ErrorContext );

	useEffect( () => {
		if ( error || Object.keys( settings ).length || fetchingSiteSettings ) {
			return () => undefined;
		}

		let unmounted = false;

		( async () => {
			try {
				const fetchedSiteSettings = await apiFetch( { path: '/wp/v2/settings' } );

				if ( unmounted ) {
					return;
				}

				setSettings( fetchedSiteSettings );
			} catch ( e ) {
				if ( unmounted ) {
					return;
				}

				setError( e );
				return;
			}

			setFetchingSiteSettings( false );
		} )();

		return () => {
			unmounted = true;
		};
	}, [ error, settings, fetchingSiteSettings, setError ] );

	return (
		<SiteSettings.Provider value={ { settings, fetchingSiteSettings } }>
			{ children }
		</SiteSettings.Provider>
	);
}

SiteSettingsProvider.propTypes = {
	children: PropTypes.any,
};
