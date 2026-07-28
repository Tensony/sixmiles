<?php if (! defined('BASEPATH')) exit('No direct script access allowed');

if (! function_exists('supabase_sync_request')) {
    /**
     * Executes a cURL request to the Supabase REST API
     *
     * @param string $method HTTP method (POST, PATCH, DELETE)
     * @param string $table The table name
     * @param string $query_params URL query parameters
     * @param array|null $data Associative array of column data
     * @return void
     */
    function supabase_sync_request($method, $table, $query_params = '', $data = NULL)
    {
        log_message('debug', "Supabase sync request triggered for table: $table, method: $method");
        file_put_contents('c:/xampp/htdocs/sixmiles/supabase_sync_log.txt', "[" . date('Y-m-d H:i:s') . "] TRIGGERED ($method $table)\n", FILE_APPEND);
        
        /** @var CI_Controller $CI */
        $CI = &get_instance();
        $CI->load->config('supabase');

        /** @var CI_Config $config */
        $config =& load_class('Config', 'core');

        $enabled = $config->item('supabase_sync_enabled');
        if (!$enabled) {
            return;
        }

        $url = $config->item('supabase_url');
        $key = $config->item('supabase_key');

        if (empty($url) || empty($key)) {
            log_message('error', 'Supabase sync: URL or Key is missing in configuration.');
            return;
        }

        if (!function_exists('curl_init')) {
            log_message('error', 'Supabase sync: cURL extension is not enabled in PHP.');
            return;
        }

        // Filter out fields that do not exist in the database table
        if ($data !== NULL && $method !== 'DELETE') {
            $db_conn = NULL;
            if (property_exists($CI, 'dbmodel') && isset($CI->{"dbmodel"}->theDB)) {
                $db_conn = $CI->{"dbmodel"}->theDB;
            } else {
                $db_conn = property_exists($CI, 'db') ? $CI->{"db"} : NULL;
            }

            if ($db_conn && method_exists($db_conn, 'list_fields')) {
                try {
                    $fields = $db_conn->list_fields($table);
                    if (is_array($fields) && !empty($fields)) {
                        $data = array_intersect_key($data, array_flip($fields));
                    }
                } catch (Exception $e) {
                    log_message('error', 'Supabase sync: column filtering failed: ' . $e->getMessage());
                }
            }
        }

        $url = rtrim($url, '/');
        $request_url = $url . '/' . $table . ($query_params ? '?' . $query_params : '');

        $ch = curl_init($request_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);

        $headers = array(
            "apikey: " . $key,
            "Authorization: Bearer " . $key,
            "Content-Type: application/json"
        );

        if ($method === 'POST') {
            $headers[] = "Prefer: resolution=merge-duplicates";
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(array($data)));
        } elseif ($method === 'PATCH' && $data !== NULL) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }

        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        curl_close($ch);

        if ($curl_error) {
            log_message('error', "Supabase sync error ($method $table): " . $curl_error);
            file_put_contents('c:/xampp/htdocs/sixmiles/supabase_sync_log.txt', "[" . date('Y-m-d H:i:s') . "] ERROR ($method $table): $curl_error\n", FILE_APPEND);
        } else {
            log_message('debug', "Supabase sync response ($method $table) - HTTP $http_code: " . $response);
            file_put_contents('c:/xampp/htdocs/sixmiles/supabase_sync_log.txt', "[" . date('Y-m-d H:i:s') . "] RESPONSE ($method $table) - HTTP $http_code: $response\n", FILE_APPEND);
        }
    }
}

if (! function_exists('should_sync_table')) {
    /**
     * Checks if a database/table should be synchronized
     *
     * @param string $db Database name
     * @param string $table Table name
     * @return bool
     */
    function should_sync_table($db, $table)
    {
        file_put_contents('c:/xampp/htdocs/sixmiles/supabase_sync_log.txt', "[" . date('Y-m-d H:i:s') . "] CHECKING TABLE ($db.$table)\n", FILE_APPEND);
        if (strpos($table, 'dbapp_') === 0) {
            return FALSE;
        }

        /** @var CI_Controller $CI */
        $CI = &get_instance();
        $CI->load->config('supabase');

        /** @var CI_Config $config */
        $config =& load_class('Config', 'core');

        $synced_tables = (array) $config->item('supabase_synced_tables');

        if (empty($synced_tables)) {
            return TRUE;
        }

        if (isset($synced_tables[$db])) {
            $rules = $synced_tables[$db];
            if ($rules === '*' || (is_array($rules) && in_array($table, $rules))) {
                return TRUE;
            }
        }

        return FALSE;
    }
}

if (! function_exists('supabase_sync_insert')) {
    /**
     * Syncs insert operations to Supabase
     *
     * @param string $db Database name
     * @param string $table Table name
     * @param string|null $primaryKeyName Primary key column name
     * @param mixed $recordID Record ID
     * @param array $data Column data being inserted
     * @return void
     */
    function supabase_sync_insert($db, $table, $primaryKeyName, $recordID, $data)
    {
        file_put_contents('c:/xampp/htdocs/sixmiles/supabase_sync_log.txt', "[" . date('Y-m-d H:i:s') . "] INSERT HOOK CALLED ($db.$table, ID: $recordID)\n", FILE_APPEND);
        if (!should_sync_table($db, $table)) {
            return;
        }

        if ($primaryKeyName !== NULL && $recordID !== NULL) {
            $data[$primaryKeyName] = $recordID;
        }

        supabase_sync_request('POST', $table, '', $data);
    }
}

if (! function_exists('supabase_sync_update')) {
    /**
     * Syncs update operations to Supabase
     *
     * @param string $db Database name
     * @param string $table Table name
     * @param string $indexName Primary key column name
     * @param mixed $recordID Record ID
     * @param array $data Column data being updated
     * @return void
     */
    function supabase_sync_update($db, $table, $indexName, $recordID, $data)
    {
        if (!should_sync_table($db, $table)) {
            return;
        }

        $query = $indexName . '=eq.' . rawurlencode($recordID);
        supabase_sync_request('PATCH', $table, $query, $data);
    }
}

if (! function_exists('supabase_sync_delete')) {
    /**
     * Syncs delete operations to Supabase
     *
     * @param string $db Database name
     * @param string $table Table name
     * @param string $indexName Primary key column name
     * @param mixed $recordID Record ID
     * @return void
     */
    function supabase_sync_delete($db, $table, $indexName, $recordID)
    {
        if (!should_sync_table($db, $table)) {
            return;
        }

        $query = $indexName . '=eq.' . rawurlencode($recordID);
        supabase_sync_request('DELETE', $table, $query);
    }
}

if (! function_exists('supabase_pull_sync')) {
    /**
     * Pulls data from Supabase and synchronizes it to local database
     *
     * @param string $db Local database name
     * @param string $table Local table name
     * @return array Sync status statistics
     */
    function supabase_pull_sync($db, $table)
    {
        $stats = array('inserted' => 0, 'updated' => 0, 'deleted' => 0, 'error' => '');

        if (!should_sync_table($db, $table)) {
            $stats['error'] = 'Table syncing is disabled or not configured for this table.';
            return $stats;
        }

        /** @var CI_Controller $CI */
        $CI = &get_instance();
        $CI->load->config('supabase');

        /** @var CI_Config $config */
        $config =& load_class('Config', 'core');

        $enabled = $config->item('supabase_sync_enabled');
        if (!$enabled) {
            $stats['error'] = 'Supabase sync is disabled.';
            return $stats;
        }

        $url = $config->item('supabase_url');
        $key = $config->item('supabase_key');

        if (empty($url) || empty($key)) {
            $stats['error'] = 'Supabase URL or Key is missing.';
            return $stats;
        }

        // Get primary key name for local table
        $CI->load->model('tablemodel');
        $CI->load->model('dbmodel');
        $CI->{"dbmodel"}->initialize($db);
        $field = $CI->{"tablemodel"}->getPrimaryKey($table);
        $primaryKeyName = $field ? $field->name : NULL;

        if (empty($primaryKeyName)) {
            $stats['error'] = 'No primary key found for local table.';
            return $stats;
        }

        // Fetch records from Supabase
        $url = rtrim($url, '/');
        $request_url = $url . '/' . $table;

        $ch = curl_init($request_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            "apikey: " . $key,
            "Authorization: Bearer " . $key,
            "Content-Type: application/json"
        ));
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        curl_close($ch);

        if ($curl_error) {
            $stats['error'] = 'cURL error: ' . $curl_error;
            log_message('error', 'Supabase pull error: ' . $curl_error);
            return $stats;
        }

        if ($http_code !== 200) {
            $stats['error'] = 'Supabase API returned HTTP code: ' . $http_code . ' Response: ' . $response;
            log_message('error', 'Supabase pull error. HTTP ' . $http_code . ': ' . $response);
            return $stats;
        }

        $records = json_decode($response, TRUE);
        if (!is_array($records)) {
            $stats['error'] = 'Failed to parse JSON response from Supabase.';
            return $stats;
        }

        $local_fields = $CI->{"dbmodel"}->theDB->list_fields($table);
        $fields_flip = array_flip($local_fields);

        foreach ($records as $record) {
            if (!isset($record[$primaryKeyName])) {
                continue;
            }

            $record_id = $record[$primaryKeyName];

            // Filter columns to contain only those present in local database
            $filtered_data = array_intersect_key($record, $fields_flip);

            // Check if record exists locally
            $existing = $CI->{"dbmodel"}->theDB->from($table)->where($primaryKeyName, $record_id)->get()->row_array();

            if ($existing) {
                // Check if any fields differ
                $differs = FALSE;
                foreach ($filtered_data as $col => $val) {
                    if ($existing[$col] != $val) {
                        $differs = TRUE;
                        break;
                    }
                }

                if ($differs) {
                    $CI->{"dbmodel"}->theDB->where($primaryKeyName, $record_id)->update($table, $filtered_data);
                    $stats['updated']++;
                }
            } else {
                $CI->{"dbmodel"}->theDB->insert($table, $filtered_data);
                $stats['inserted']++;
            }
        }

        // Write pull to sync log
        file_put_contents(
            'c:/xampp/htdocs/sixmiles/supabase_sync_log.txt',
            "[" . date('Y-m-d H:i:s') . "] PULL SYNC ($db.$table) - Fetched: " . count($records) . ", Inserted: " . $stats['inserted'] . ", Updated: " . $stats['updated'] . "\n",
            FILE_APPEND
        );

        return $stats;
    }
}
