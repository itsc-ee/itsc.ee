<?php
/**
 * Plugin Name: ITSC ACF Custom Repeater Field
 * Description: Adds a lightweight custom_repeater field type for ACF and maps old repeater fields to it.
 * Version: 1.0.0
 * Author: ITSC
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action(
	'acf/include_field_types',
	function () {
		if ( ! class_exists( 'acf_field' ) || class_exists( 'acf_field_itsc_custom_repeater' ) ) {
			return;
		}

		class acf_field_itsc_custom_repeater extends acf_field {
			public function initialize() {
				$this->name        = 'custom_repeater';
				$this->label       = __( 'Custom Repeater', 'acf' );
				$this->category    = 'layout';
				$this->description = __( 'Repeat a set of sub fields.', 'acf' );
				$this->defaults    = array(
					'sub_fields'   => array(),
					'layout'       => 'block',
					'min'          => 0,
					'max'          => 0,
					'button_label' => __( 'Add Row', 'acf' ),
				);
				$this->have_rows   = 'multi';

				$this->add_field_filter( 'acf/prepare_field_for_export', array( $this, 'prepare_field_for_export' ) );
				$this->add_field_filter( 'acf/prepare_field_for_import', array( $this, 'prepare_field_for_import' ) );
			}

			public function load_field( $field ) {
				$sub_fields = acf_get_fields( $field );
				if ( $sub_fields ) {
					$field['sub_fields'] = $sub_fields;
				}

				return $field;
			}

			public function load_value( $value, $post_id, $field ) {
				if ( empty( $field['sub_fields'] ) ) {
					return false;
				}

				$total = absint( $value );
				if ( ! $total ) {
					return false;
				}

				$rows = array();
				for ( $i = 0; $i < $total; $i++ ) {
					$row = array();
					foreach ( $field['sub_fields'] as $sub_field ) {
						$sub_field               = $this->prepare_sub_field_for_db( $sub_field, $field, $i );
						$row[ $sub_field['key'] ] = get_post_meta( $post_id, $sub_field['name'], true );
					}
					$rows[] = $row;
				}

				return $rows;
			}

			public function update_value( $value, $post_id, $field ) {
				if ( empty( $field['sub_fields'] ) || ! is_array( $value ) ) {
					return 0;
				}

				unset( $value['acfcloneindex'] );
				$rows = array_values( array_filter( $value, 'is_array' ) );

				foreach ( $rows as $i => $row ) {
					foreach ( $field['sub_fields'] as $sub_field ) {
						$input_key  = $sub_field['key'];
						$input_name = isset( $sub_field['_name'] ) ? $sub_field['_name'] : $sub_field['name'];

						if ( array_key_exists( $input_key, $row ) ) {
							$input_value = $row[ $input_key ];
						} elseif ( array_key_exists( $input_name, $row ) ) {
							$input_value = $row[ $input_name ];
						} else {
							continue;
						}

						$prepared_sub_field = $this->prepare_sub_field_for_db( $sub_field, $field, $i );
						acf_update_value( $input_value, $post_id, $prepared_sub_field );
					}
				}

				$old_total = absint( get_post_meta( $post_id, $field['name'], true ) );
				for ( $i = count( $rows ); $i < $old_total; $i++ ) {
					foreach ( $field['sub_fields'] as $sub_field ) {
						$prepared_sub_field = $this->prepare_sub_field_for_db( $sub_field, $field, $i );
						acf_delete_value( $post_id, $prepared_sub_field );
					}
				}

				return count( $rows );
			}

			public function format_value( $value, $post_id, $field, $escape_html = false ) {
				if ( empty( $value ) || ! is_array( $value ) || empty( $field['sub_fields'] ) ) {
					return false;
				}

				foreach ( $value as $i => &$row ) {
					foreach ( $field['sub_fields'] as $sub_field ) {
						$sub_field = $this->prepare_sub_field_for_db( $sub_field, $field, $i );
						$sub_value = isset( $row[ $sub_field['key'] ] ) ? $row[ $sub_field['key'] ] : null;
						$name      = isset( $sub_field['_name'] ) ? $sub_field['_name'] : $sub_field['name'];

						$row[ $name ] = acf_format_value( $sub_value, $post_id, $sub_field, $escape_html );
						unset( $row[ $sub_field['key'] ] );
					}
				}

				return $value;
			}

			public function validate_value( $valid, $value, $field, $input ) {
				if ( is_array( $value ) ) {
					unset( $value['acfcloneindex'] );
				}

				$rows  = is_array( $value ) ? array_values( array_filter( $value, 'is_array' ) ) : array();
				$count = count( $rows );
				$min   = absint( isset( $field['min'] ) ? $field['min'] : 0 );
				$max   = absint( isset( $field['max'] ) ? $field['max'] : 0 );

				if ( $min && $count < $min ) {
					return sprintf( __( 'Minimum rows not reached (%d rows)', 'acf' ), $min );
				}

				if ( $max && $count > $max ) {
					return sprintf( __( 'Maximum rows reached (%d rows)', 'acf' ), $max );
				}

				foreach ( $rows as $i => $row ) {
					foreach ( $field['sub_fields'] as $sub_field ) {
						$key = $sub_field['key'];
						if ( array_key_exists( $key, $row ) ) {
							acf_validate_value( $row[ $key ], $sub_field, "{$input}[{$i}][{$key}]" );
						}
					}
				}

				return $valid;
			}

			public function render_field( $field ) {
				if ( empty( $field['sub_fields'] ) ) {
					echo '<p>' . esc_html__( 'No sub fields configured.', 'acf' ) . '</p>';
					return;
				}

				$rows = is_array( $field['value'] ) ? array_values( $field['value'] ) : array();
				$min  = absint( isset( $field['min'] ) ? $field['min'] : 0 );
				while ( count( $rows ) < $min ) {
					$rows[] = array();
				}

				?>
				<div class="itsc-acf-custom-repeater" data-min="<?php echo esc_attr( absint( isset( $field['min'] ) ? $field['min'] : 0 ) ); ?>" data-max="<?php echo esc_attr( absint( isset( $field['max'] ) ? $field['max'] : 0 ) ); ?>">
					<div class="itsc-acf-custom-repeater-rows">
						<?php
						foreach ( $rows as $i => $row ) {
							$this->render_row( $field, $row, $i );
						}
						$this->render_row( $field, array(), 'acfcloneindex', true );
						?>
					</div>
					<p class="acf-actions">
						<a href="#" class="button button-primary itsc-acf-custom-repeater-add-row"><?php echo esc_html( ! empty( $field['button_label'] ) ? $field['button_label'] : __( 'Add Row', 'acf' ) ); ?></a>
					</p>
				</div>
				<?php
			}

			private function render_row( $field, $row, $index, $is_clone = false ) {
				$row_class = $is_clone ? ' itsc-acf-custom-repeater-clone acf-clone' : '';
				?>
				<div class="itsc-acf-custom-repeater-row<?php echo esc_attr( $row_class ); ?>" data-index="<?php echo esc_attr( $index ); ?>">
					<div class="itsc-acf-custom-repeater-row-toolbar">
						<span class="itsc-acf-custom-repeater-row-title"><?php echo esc_html__( 'Row', 'acf' ); ?> <span class="itsc-acf-custom-repeater-row-number"></span></span>
						<a href="#" class="acf-icon -minus small itsc-acf-custom-repeater-remove-row" title="<?php esc_attr_e( 'Remove row', 'acf' ); ?>"></a>
					</div>
					<div class="acf-fields -top -border">
						<?php
						foreach ( $field['sub_fields'] as $sub_field ) {
							$sub_field = $this->prepare_sub_field_for_render( $sub_field, $field, $row, $index );
							acf_render_field_wrap( $sub_field );
						}
						?>
					</div>
				</div>
				<?php
			}

			private function prepare_sub_field_for_render( $sub_field, $field, $row, $index ) {
				$sub_field['value']  = isset( $row[ $sub_field['key'] ] ) ? $row[ $sub_field['key'] ] : ( isset( $sub_field['default_value'] ) ? $sub_field['default_value'] : null );
				$sub_field['prefix'] = $field['name'] . '[' . $index . ']';

				if ( ! empty( $field['required'] ) ) {
					$sub_field['required'] = 0;
				}

				return $sub_field;
			}

			private function prepare_sub_field_for_db( $sub_field, $field, $index ) {
				$sub_name           = isset( $sub_field['_name'] ) ? $sub_field['_name'] : $sub_field['name'];
				$sub_field['_name'] = $sub_name;
				$sub_field['name']  = $field['name'] . '_' . $index . '_' . $sub_name;

				return $sub_field;
			}

			public function render_field_settings( $field ) {
				$args = array(
					'fields'      => $field['sub_fields'],
					'parent'      => $field['ID'],
					'is_subfield' => true,
				);
				?>
				<div class="acf-field acf-field-setting-sub_fields" data-setting="group" data-name="sub_fields">
					<div class="acf-label">
						<label><?php esc_html_e( 'Sub Fields', 'acf' ); ?></label>
					</div>
					<div class="acf-input acf-input-sub">
						<?php acf_get_view( 'acf-field-group/fields', $args ); ?>
					</div>
				</div>
				<?php

				acf_render_field_setting(
					$field,
					array(
						'label'   => __( 'Layout', 'acf' ),
						'type'    => 'radio',
						'name'    => 'layout',
						'layout'  => 'horizontal',
						'choices' => array(
							'block' => __( 'Block', 'acf' ),
							'row'   => __( 'Row', 'acf' ),
							'table' => __( 'Table', 'acf' ),
						),
					)
				);

				acf_render_field_setting( $field, array( 'label' => __( 'Minimum Rows', 'acf' ), 'type' => 'number', 'name' => 'min', 'min' => 0 ) );
				acf_render_field_setting( $field, array( 'label' => __( 'Maximum Rows', 'acf' ), 'type' => 'number', 'name' => 'max', 'min' => 0 ) );
				acf_render_field_setting( $field, array( 'label' => __( 'Button Label', 'acf' ), 'type' => 'text', 'name' => 'button_label' ) );
			}

			public function duplicate_field( $field ) {
				$sub_fields = acf_extract_var( $field, 'sub_fields' );
				$field      = acf_update_field( $field );
				acf_duplicate_fields( $sub_fields, $field['ID'] );

				return $field;
			}

			public function prepare_field_for_export( $field ) {
				if ( ! empty( $field['sub_fields'] ) ) {
					$field['sub_fields'] = acf_prepare_fields_for_export( $field['sub_fields'] );
				}

				return $field;
			}

			public function prepare_field_for_import( $field ) {
				if ( ! empty( $field['sub_fields'] ) ) {
					$sub_fields = acf_extract_var( $field, 'sub_fields' );

					foreach ( $sub_fields as $i => $sub_field ) {
						$sub_fields[ $i ]['parent']     = $field['key'];
						$sub_fields[ $i ]['menu_order'] = $i;
					}

					return array_merge( array( $field ), $sub_fields );
				}

				return $field;
			}
		}

		acf_register_field_type( 'acf_field_itsc_custom_repeater' );
	}
);

add_filter(
	'acf/load_field',
	function ( $field ) {
		$is_acf_pro = function_exists( 'acf_is_pro' ) && acf_is_pro();

		if ( isset( $field['type'] ) && 'repeater' === $field['type'] && ! $is_acf_pro ) {
			$field['type'] = 'custom_repeater';
		}

		return $field;
	},
	1
);

add_action(
	'acf/input/admin_footer',
	function () {
		?>
		<style>
			.itsc-acf-custom-repeater-row {
				margin: 0 0 12px;
				background: #fff;
			}
			.itsc-acf-custom-repeater-row-toolbar {
				display: flex;
				align-items: center;
				justify-content: space-between;
				padding: 8px 10px;
				border: 1px solid #ccd0d4;
				border-bottom: 0;
				background: #f6f7f7;
			}
			.itsc-acf-custom-repeater-row-title {
				font-weight: 600;
			}
			.itsc-acf-custom-repeater-clone {
				display: none !important;
			}
		</style>
		<script>
			(function($) {
				function renumber($repeater) {
					var $rows = $repeater.find('> .itsc-acf-custom-repeater-rows > .itsc-acf-custom-repeater-row').not('.itsc-acf-custom-repeater-clone');
					var max = parseInt($repeater.data('max'), 10) || 0;

					$rows.each(function(index) {
						$(this).attr('data-index', index).find('.itsc-acf-custom-repeater-row-number').text(index + 1);
					});

					$repeater.find('> .acf-actions .itsc-acf-custom-repeater-add-row').toggle(!max || $rows.length < max);
				}

				function replaceIndex(value, index) {
					return value.split('acfcloneindex').join(index);
				}

				$(document).on('click', '.itsc-acf-custom-repeater-add-row', function(e) {
					e.preventDefault();

					var $repeater = $(this).closest('.itsc-acf-custom-repeater');
					var $rows = $repeater.find('> .itsc-acf-custom-repeater-rows > .itsc-acf-custom-repeater-row').not('.itsc-acf-custom-repeater-clone');
					var max = parseInt($repeater.data('max'), 10) || 0;

					if (max && $rows.length >= max) {
						return;
					}

					var index = $rows.length;
					var $clone = $repeater.find('> .itsc-acf-custom-repeater-rows > .itsc-acf-custom-repeater-clone').first();
					var html = replaceIndex($clone.prop('outerHTML'), index);
					var $row = $(html).removeClass('itsc-acf-custom-repeater-clone acf-clone').show();

					$row.insertBefore($clone);
					if (window.acf) {
						acf.doAction('append', $row);
					}
					renumber($repeater);
				});

				$(document).on('click', '.itsc-acf-custom-repeater-remove-row', function(e) {
					e.preventDefault();

					var $repeater = $(this).closest('.itsc-acf-custom-repeater');
					var min = parseInt($repeater.data('min'), 10) || 0;
					var $rows = $repeater.find('> .itsc-acf-custom-repeater-rows > .itsc-acf-custom-repeater-row').not('.itsc-acf-custom-repeater-clone');

					if ($rows.length <= min) {
						return;
					}

					$(this).closest('.itsc-acf-custom-repeater-row').remove();
					renumber($repeater);
				});

				$(function() {
					$('.itsc-acf-custom-repeater').each(function() {
						renumber($(this));
					});
				});
			})(jQuery);
		</script>
		<?php
	}
);
