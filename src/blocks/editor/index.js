/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { addFilter } from '@wordpress/hooks';
import { createHigherOrderComponent } from '@wordpress/compose';
import { InspectorControls } from '@wordpress/block-editor';
import {
	BaseControl,
	CheckboxControl,
	PanelBody,
	RadioControl,
} from '@wordpress/components';

/**
 * Internal dependencies
 */
import { SUPPORTED_BLOCKS } from '../constants';

/**
 * Nav Menu Roles settings panel.
 *
 * @param {Object}   props               Component props.
 * @param {Object}   props.attributes    Block attributes.
 * @param {Function} props.setAttributes Block attribute setter.
 */
const NavMenuRolesPanel = ( { attributes, setAttributes } ) => {
	const { nmrDisplayMode, nmrAudience, nmrRoles } = attributes;
	const roles = Array.isArray( nmrRoles ) ? nmrRoles : [];
	const availableRoles = window.navMenuRolesData?.roles || {};

	const toggleRole = ( role, isChecked ) => {
		setAttributes( {
			nmrRoles: isChecked
				? [ ...roles, role ]
				: roles.filter( ( r ) => r !== role ),
		} );
	};

	return (
		<PanelBody
			title={ __( 'Nav Menu Roles', 'nav-menu-roles' ) }
			initialOpen={ nmrAudience !== '' || nmrDisplayMode === 'hide' }
		>
			<RadioControl
				label={ __( 'Display Mode', 'nav-menu-roles' ) }
				selected={ nmrDisplayMode || 'show' }
				options={ [
					{ label: __( 'Show', 'nav-menu-roles' ), value: 'show' },
					{ label: __( 'Hide', 'nav-menu-roles' ), value: 'hide' },
				] }
				onChange={ ( value ) =>
					setAttributes( { nmrDisplayMode: value } )
				}
			/>

			<RadioControl
				label={ __( 'Target audience', 'nav-menu-roles' ) }
				selected={ nmrAudience || '' }
				options={ [
					{ label: __( 'Everyone', 'nav-menu-roles' ), value: '' },
					{
						label: __( 'Logged In Users', 'nav-menu-roles' ),
						value: 'in',
					},
					{
						label: __( 'Logged Out Users', 'nav-menu-roles' ),
						value: 'out',
					},
				] }
				onChange={ ( value ) =>
					setAttributes( {
						nmrAudience: value,
						// Roles only apply to logged in users.
						...( value !== 'in' && { nmrRoles: [] } ),
					} )
				}
			/>

			{ nmrAudience === 'in' && (
				<BaseControl
					id="nav-menu-roles-target-role"
					label={ __( 'Target role', 'nav-menu-roles' ) }
					help={ __(
						'Leave unchecked to target all logged in users.',
						'nav-menu-roles'
					) }
				>
					{ Object.entries( availableRoles ).map(
						( [ role, label ] ) => (
							<CheckboxControl
								key={ role }
								label={ label }
								checked={ roles.includes( role ) }
								onChange={ ( isChecked ) =>
									toggleRole( role, isChecked )
								}
							/>
						)
					) }
				</BaseControl>
			) }
		</PanelBody>
	);
};

/**
 * Add the Nav Menu Roles panel to supported navigation blocks.
 */
const withNavMenuRolesControls = createHigherOrderComponent(
	( BlockEdit ) => ( props ) => {
		if ( ! SUPPORTED_BLOCKS.includes( props.name ) ) {
			return <BlockEdit { ...props } />;
		}

		return (
			<>
				<BlockEdit { ...props } />
				<InspectorControls>
					<NavMenuRolesPanel { ...props } />
				</InspectorControls>
			</>
		);
	},
	'withNavMenuRolesControls'
);

addFilter(
	'editor.BlockEdit',
	'nav-menu-roles/add-inspector-controls',
	withNavMenuRolesControls
);
