/**
 * Customizer scripts.
 *
 * @package Nav Menu Roles
 */

( function ( api ) {

	// Augment each menu item control once it is added and embedded.
	api.control.bind(
		'add',
		( control ) => {
			if ( control.extended( api.Menus.MenuItemControl ) ) {
				control.deferred.embedded.done(
				() => {
					extendControl( control );
					}
				);
			}
		}
	);

	/**
	 * Extend the control with roles information.
	 *
	 * @param {wp.customize.Menus.MenuItemControl} control
	 */
	function extendControl( control ) {
		control.modeFieldset  = control.container.find( '.nav_menu_role_display_mode' );
        control.authFieldset  = control.container.find( '.nav_menu_role_authentication' );
		control.rolesFieldset = control.container.find( '.nav_menu_roles' );

		// Set the initial UI state.
		updateControlFields( control );

		// Update the UI state when the setting changes programmatically.
		control.setting.bind(
			() => {
				updateControlFields( control );
			}
		);

		// Update the setting when the inputs are modified.
		control.modeFieldset.find( 'input' ).on(
			'click',
			function () {
				setSettingMode( control.setting, this.value );
			}
		);
        control.authFieldset.find( 'input' ).on(
			'click',
			function () {
				setSettingRoles( control.setting, this.value );
			}
		);
		control.rolesFieldset.find( 'input' ).on(
			'click',
			function () {
				const checkedRoles = [];
				control.rolesFieldset.find( ':checked' ).each(
					function () {
						checkedRoles.push( this.value );
					}
				);
				setSettingRoles( control.setting, checkedRoles.length === 0 ? 'in' : checkedRoles );
			}
		);
	}

	/**
	 * Extend the setting with display  mode information.
	 *
	 * @param {wp.customize.Setting} setting
	 * @param {string} mode
	 */
	function setSettingMode( setting, mode ) {

		setting.set(
			Object.assign(
				{},
				_.clone( setting() ),
				{ display_mode: mode }
			)
		);

	}

	/**
	 * Extend the setting with roles information.
	 *
	 * @param {wp.customize.Setting} setting
	 * @param {string|Array} roles
	 */
	function setSettingRoles( setting, roles ) {

        // NB: { roles } converts the roles variable to object with roles key, { roles: value }
		setting.set(
			Object.assign(
				{},
				_.clone( setting() ),
				{ roles }
			)
		);

	}

	/**
	 * Apply the control's setting value to the control's fields.
	 *
	 * @param {wp.customize.Menus.MenuItemControl} control
	 */
	function updateControlFields( control ) {
		const roles = control.setting().roles || '';

        const modeValue     = 'hide' === control.setting().display_mode ? 'hide' : 'show';
		const radioValue   = _.isArray( roles ) ? 'in' : roles;
		const checkedRoles = _.isArray( roles ) ? roles : [];

		control.rolesFieldset.toggle( 'in' === radioValue );

        const modeRadio = control.modeFieldset.find( `input[type = radio][value = "${ modeValue }"]` );
		const authRadio = control.authFieldset.find( `input[type = radio][value = "${ radioValue }"]` );

		modeRadio.prop( 'checked', true );
        authRadio.prop( 'checked', true );

		control.rolesFieldset.find( 'input[type=checkbox]' ).each(
			function () {
				this.checked = checkedRoles.includes( this.value );
			}
		);
	}
})( wp.customize );
