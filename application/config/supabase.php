<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

// Set to TRUE to enable syncing, or FALSE to disable syncing.
$config['supabase_sync_enabled'] = TRUE;

// Your Supabase REST API URL endpoint
$config['supabase_url'] = 'https://spjjjqpqxtdlpuplslkm.supabase.co/rest/v1/';

// Your Supabase Anon Key
$config['supabase_key'] = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6InNwampqcXBxeHRkbHB1cGxzbGttIiwicm9sZSI6ImFub24iLCJpYXQiOjE3ODM1NDg5OTQsImV4cCI6MjA5OTEyNDk5NH0.ZdZx7agJIdGfO3D6L3cWGI9Xju-KoF13TFi_URIPz38';

// List of databases and tables that should be synchronized.
// Leave this empty array() to sync ALL tables across databases.
$config['supabase_synced_tables'] = array();
