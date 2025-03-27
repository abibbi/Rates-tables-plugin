<?php
/*
 * Plugin Name: Credit Union Rate Tables
 * Plugin URI: https://example.com/plugins/credit-union-rate-tables/
 * Description: Display and manage interest rate tables for credit unions.
 * Version: 1.0.0
 * Author: Anthony Bibbins
 * Author URI: https://example.com
 * Text Domain: credit-union-rate-tables
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Add menu item to WordPress admin
function rate_tables_menu() {
    add_menu_page(
        'Rate Tables',
        'Rate Tables',
        'manage_options',
        'rate-tables',
        'display_rate_tables_page',
        'dashicons-calculator',
        6
    );
}
add_action('admin_menu', 'rate_tables_menu');

// Add required styles
function rate_tables_styles() {
    wp_enqueue_style('rate-tables-style', plugins_url('style.css', __FILE__));
}
add_action('admin_enqueue_scripts', 'rate_tables_styles');
add_action('wp_enqueue_scripts', 'rate_tables_styles');

// Initialize default rates if they don't exist
function initialize_default_rates() {
    $default_rates = array(
        'certificate_rates' => array(
            'columns' => array(
                array('key' => 'term', 'label' => 'Term'),
                array('key' => 'rate', 'label' => 'Rate (%)'),
                array('key' => 'min_deposit', 'label' => 'Minimum Deposit')
            ),
            'rows' => array(
                array('term' => '6 Months', 'rate' => '4.50', 'min_deposit' => '1000'),
                array('term' => '12 Months', 'rate' => '5.00', 'min_deposit' => '1000'),
                array('term' => '24 Months', 'rate' => '5.25', 'min_deposit' => '1000'),
                array('term' => '36 Months', 'rate' => '5.50', 'min_deposit' => '1000'),
            )
        ),
        'savings_rates' => array(
            'columns' => array(
                array('key' => 'type', 'label' => 'Account Type'),
                array('key' => 'rate', 'label' => 'Rate (%)'),
                array('key' => 'min_balance', 'label' => 'Minimum Balance')
            ),
            'rows' => array(
                array('type' => 'Regular Savings', 'rate' => '2.00', 'min_balance' => '100'),
                array('type' => 'Money Market', 'rate' => '3.50', 'min_balance' => '2500'),
                array('type' => 'High-Yield Savings', 'rate' => '4.00', 'min_balance' => '10000'),
            )
        )
    );
    
    if (!get_option('credit_union_rates')) {
        update_option('credit_union_rates', $default_rates);
    }
}
register_activation_hook(__FILE__, 'initialize_default_rates');

// Display and manage rate tables
function display_rate_tables_page() {
    $rates = get_option('credit_union_rates');
    
    // Handle form submission
    if (isset($_POST['update_rates']) && check_admin_referer('update_rates_nonce')) {
        $new_rates = array(
            'certificate_rates' => array(
                'columns' => array(),
                'rows' => array()
            ),
            'savings_rates' => array(
                'columns' => array(),
                'rows' => array()
            )
        );
        
        // Update Certificate Columns
        foreach ($_POST['cert_column_key'] as $key => $column_key) {
            if (!empty($column_key) && isset($_POST['cert_column_label'][$key])) {
                $new_rates['certificate_rates']['columns'][] = array(
                    'key' => sanitize_text_field($column_key),
                    'label' => sanitize_text_field($_POST['cert_column_label'][$key])
                );
            }
        }
        
        // Update Certificate Rows
        if (isset($_POST['cert_rows']) && is_array($_POST['cert_rows'])) {
            foreach ($_POST['cert_rows'] as $row_index => $row_data) {
                $row = array();
                foreach ($new_rates['certificate_rates']['columns'] as $col) {
                    $row[$col['key']] = isset($row_data[$col['key']]) ? sanitize_text_field($row_data[$col['key']]) : '';
                }
                $new_rates['certificate_rates']['rows'][] = $row;
            }
        }
        
        // Update Savings Columns
        foreach ($_POST['savings_column_key'] as $key => $column_key) {
            if (!empty($column_key) && isset($_POST['savings_column_label'][$key])) {
                $new_rates['savings_rates']['columns'][] = array(
                    'key' => sanitize_text_field($column_key),
                    'label' => sanitize_text_field($_POST['savings_column_label'][$key])
                );
            }
        }
        
        // Update Savings Rows
        if (isset($_POST['savings_rows']) && is_array($_POST['savings_rows'])) {
            foreach ($_POST['savings_rows'] as $row_index => $row_data) {
                $row = array();
                foreach ($new_rates['savings_rates']['columns'] as $col) {
                    $row[$col['key']] = isset($row_data[$col['key']]) ? sanitize_text_field($row_data[$col['key']]) : '';
                }
                $new_rates['savings_rates']['rows'][] = $row;
            }
        }
        
        update_option('credit_union_rates', $new_rates);
        $rates = $new_rates;
        echo '<div class="notice notice-success"><p>Rates updated successfully!</p></div>';
    }
    
    // Admin page HTML
    ?>
    <div class="wrap">
        <h1>Credit Union Rate Tables</h1>
        <form method="post" action="">
            <?php wp_nonce_field('update_rates_nonce'); ?>
            
            <h2>Certificate Rates</h2>
            <div class="column-management">
                <h3>Columns</h3>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>Column Key</th>
                            <th>Display Label</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody id="cert-columns-tbody">
                        <?php foreach ($rates['certificate_rates']['columns'] as $column) : ?>
                        <tr>
                            <td><input type="text" name="cert_column_key[]" value="<?php echo esc_attr($column['key']); ?>" required></td>
                            <td><input type="text" name="cert_column_label[]" value="<?php echo esc_attr($column['label']); ?>" required></td>
                            <td><button type="button" class="button remove-row">Remove</button></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <button type="button" class="button" onclick="addCertColumn()">Add Column</button>
            </div>

            <h3>Rate Data</h3>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <?php foreach ($rates['certificate_rates']['columns'] as $column) : ?>
                            <th><?php echo esc_html($column['label']); ?></th>
                        <?php endforeach; ?>
                        <th></th>
                    </tr>
                </thead>
                <tbody id="cert-rates-tbody">
                    <?php foreach ($rates['certificate_rates']['rows'] as $row) : ?>
                    <tr>
                        <?php foreach ($rates['certificate_rates']['columns'] as $column) : ?>
                            <td><input type="text" name="cert_rows[<?php echo count($rates['certificate_rates']['rows']); ?>][<?php echo esc_attr($column['key']); ?>]" value="<?php echo esc_attr($row[$column['key']]); ?>" required></td>
                        <?php endforeach; ?>
                        <td><button type="button" class="button remove-row">Remove</button></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <button type="button" class="button" onclick="addCertRow()">Add Certificate Rate</button>
            
            <h2>Savings Rates</h2>
            <div class="column-management">
                <h3>Columns</h3>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>Column Key</th>
                            <th>Display Label</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody id="savings-columns-tbody">
                        <?php foreach ($rates['savings_rates']['columns'] as $column) : ?>
                        <tr>
                            <td><input type="text" name="savings_column_key[]" value="<?php echo esc_attr($column['key']); ?>" required></td>
                            <td><input type="text" name="savings_column_label[]" value="<?php echo esc_attr($column['label']); ?>" required></td>
                            <td><button type="button" class="button remove-row">Remove</button></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <button type="button" class="button" onclick="addSavingsColumn()">Add Column</button>
            </div>

            <h3>Rate Data</h3>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <?php foreach ($rates['savings_rates']['columns'] as $column) : ?>
                            <th><?php echo esc_html($column['label']); ?></th>
                        <?php endforeach; ?>
                        <th></th>
                    </tr>
                </thead>
                <tbody id="savings-rates-tbody">
                    <?php foreach ($rates['savings_rates']['rows'] as $row) : ?>
                    <tr>
                        <?php foreach ($rates['savings_rates']['columns'] as $column) : ?>
                            <td><input type="text" name="savings_rows[<?php echo count($rates['savings_rates']['rows']); ?>][<?php echo esc_attr($column['key']); ?>]" value="<?php echo esc_attr($row[$column['key']]); ?>" required></td>
                        <?php endforeach; ?>
                        <td><button type="button" class="button remove-row">Remove</button></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <button type="button" class="button" onclick="addSavingsRow()">Add Savings Rate</button>
            
            <p class="submit">
                <input type="submit" name="update_rates" class="button-primary" value="Update Rates">
            </p>
         
        </form>
    </div>

    <script>
      

      function addCertColumn() {
        const tbody = document.getElementById('cert-columns-tbody');
        const row = document.createElement('tr');
        row.innerHTML = `
            <td><input type="text" name="cert_column_key[]" required></td>
            <td><input type="text" name="cert_column_label[]" required></td>
            <td><button type="button" class="button remove-row">Remove</button></td>
        `;
        tbody.appendChild(row);
        updateCertTable();
    }

    function addSavingsColumn() {
        const tbody = document.getElementById('savings-columns-tbody');
        const row = document.createElement('tr');
        row.innerHTML = `
            <td><input type="text" name="savings_column_key[]" required></td>
            <td><input type="text" name="savings_column_label[]" required></td>
            <td><button type="button" class="button remove-row">Remove</button></td>
        `;
        tbody.appendChild(row);
        updateSavingsTable();
    }

    function addSavingsColumn() {
        const tbody = document.getElementById('savings-columns-tbody');
        const row = document.createElement('tr');
        row.innerHTML = `
            <td><input type="text" name="savings_column_key[]" required></td>
            <td><input type="text" name="savings_column_label[]" required></td>
            <td><button type="button" class="button remove-row">Remove</button></td>
        `;
        tbody.appendChild(row);
        updateSavingsTable();
    }

    function addCertRow() {
        const tbody = document.getElementById('cert-rates-tbody');
        const columns = document.querySelectorAll('#cert-columns-tbody tr');
        const row = document.createElement('tr');
        let html = '';
        
        columns.forEach((col, index) => {
            const key = col.querySelector('input[name="cert_column_key[]"]').value;
            html += `<td><input type="text" name="cert_rows[${tbody.children.length}][${key}]" required></td>`;
        });
        
        html += '<td><button type="button" class="button remove-row">Remove</button></td>';
        row.innerHTML = html;
        tbody.appendChild(row);
    }

    function addSavingsRow() {
        const tbody = document.getElementById('savings-rates-tbody');
        const columns = document.querySelectorAll('#savings-columns-tbody tr');
        const row = document.createElement('tr');
        let html = '';
        
        columns.forEach((col, index) => {
            const key = col.querySelector('input[name="savings_column_key[]"]').value;
            html += `<td><input type="text" name="savings_rows[${tbody.children.length}][${key}]" required></td>`;
        });
        
        html += '<td><button type="button" class="button remove-row">Remove</button></td>';
        row.innerHTML = html;
        tbody.appendChild(row);
    }

    function updateCertTable() {
        const tbody = document.getElementById('cert-rates-tbody');
        const columns = document.querySelectorAll('#cert-columns-tbody tr');
        const rows = tbody.querySelectorAll('tr');
        
        rows.forEach(row => {
            const inputs = row.querySelectorAll('input');
            const newRow = document.createElement('tr');
            let html = '';
            
            columns.forEach(col => {
                const key = col.querySelector('input[name="cert_column_key[]"]').value;
                const existingValue = inputs.find(input => input.name.includes(key))?.value || '';
                html += `<td><input type="text" name="${inputs[0].name.replace(/\[\d+\]/, `[${row.rowIndex}]`)}" value="${existingValue}" required></td>`;
            });
            
            html += '<td><button type="button" class="button remove-row">Remove</button></td>';
            newRow.innerHTML = html;
            row.parentNode.replaceChild(newRow, row);
        });
    }

    function updateSavingsTable() {
        const tbody = document.getElementById('savings-rates-tbody');
        const columns = document.querySelectorAll('#savings-columns-tbody tr');
        const rows = tbody.querySelectorAll('tr');
        
        rows.forEach(row => {
            const inputs = row.querySelectorAll('input');
            const newRow = document.createElement('tr');
            let html = '';
            
            columns.forEach(col => {
                const key = col.querySelector('input[name="savings_column_key[]"]').value;
                const existingValue = inputs.find(input => input.name.includes(key))?.value || '';
                html += `<td><input type="text" name="${inputs[0].name.replace(/\[\d+\]/, `[${row.rowIndex}]`)}" value="${existingValue}" required></td>`;
            });
            
            html += '<td><button type="button" class="button remove-row">Remove</button></td>';
            newRow.innerHTML = html;
            row.parentNode.replaceChild(newRow, row);
        });
    }

    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('remove-row')) {
            e.target.closest('tr').remove();
        }
    });
    </script>
    <?php
}

// Shortcode to display rate tables on the frontend
function display_rate_table_shortcode($atts) {
    $rates = get_option('credit_union_rates');
    $type = isset($atts['type']) ? $atts['type'] : 'all';
    
    ob_start();
    
    if ($type === 'all' || $type === 'certificates') {
        ?>
        <div class="rate-table-container">
            <h3>Certificate Rates</h3>
            <table class="rate-table">
                <thead>
                    <tr>
                        <?php foreach ($rates['certificate_rates']['columns'] as $column) : ?>
                            <th><?php echo esc_html($column['label']); ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rates['certificate_rates']['rows'] as $row) : ?>
                    <tr>
                        <?php foreach ($rates['certificate_rates']['columns'] as $column) : ?>
                            <td><?php echo esc_html($row[$column['key']]); ?></td>
                        <?php endforeach; ?>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }
    
    if ($type === 'all' || $type === 'savings') {
        ?>
        <div class="rate-table-container">
            <h3>Savings Rates</h3>
            <table class="rate-table">
                <thead>
                    <tr>
                        <?php foreach ($rates['savings_rates']['columns'] as $column) : ?>
                            <th><?php echo esc_html($column['label']); ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rates['savings_rates']['rows'] as $row) : ?>
                    <tr>
                        <?php foreach ($rates['savings_rates']['columns'] as $column) : ?>
                            <td><?php echo esc_html($row[$column['key']]); ?></td>
                        <?php endforeach; ?>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }
    
    return ob_get_clean();
}
add_shortcode('rate_table', 'display_rate_table_shortcode');
?>
//shortcode for the rate table
[rate_table type="certificates"]
[rate_table type="savings"]