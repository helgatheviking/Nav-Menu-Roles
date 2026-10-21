/**
 * Register Nav Menu Roles attributes on the navigation link blocks.
 */
import { addFilter } from '@wordpress/hooks';

import { SUPPORTED_BLOCKS } from './constants';

addFilter(
	'blocks.registerBlockType',
	'nav-menu-roles/navigation-link-attributes',
	( settings, name ) => {
		if ( ! SUPPORTED_BLOCKS.includes( name ) ) {
			return settings;
		}

		return {
			...settings,
			attributes: {
				...settings.attributes,
				nmrDisplayMode: {
					type: 'string',
					default: 'show',
				},
				nmrAudience: {
					type: 'string',
					default: '',
				},
				nmrRoles: {
					type: 'array',
					default: [],
					items: {
						type: 'string',
					},
				},
			},
		};
	}
);
