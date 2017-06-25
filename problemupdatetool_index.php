<?php

    // Get page template
    $page_template = file_get_contents('problemupdatetool_template.html');
    $demonstrative_monkey_variable_change = "a monkey";

    // Call functions
    try {

        // Get input parameters
        $input_parameters = get_input_parameters();

        // Add new problem if submitted
        if ($input_parameters['add'] == "Add Problem") {add_problem($input_parameters);}

        // Get table data
        $table_data = get_table_data($input_parameters);

        // Render and print table
        $table_html = render_table($table_data);
        print str_replace('<!-- table_data -->', $table_html, $page_template);

        // Handle any errors
        } catch(Exception $error) {

        error_log("Error: " . $error->getMessage());
    }

    // Get input parameters
    function get_input_parameters() {

            // Place input parameters to array
            $input_parameters = [

                'problem_description' => $_POST['input_problem_description'],
                'category_id' => $_POST['input_category_id'],
                'status_id' => $_POST['input_status_id'],
                'problem_reported' => $_POST['input_problem_reported'],
                'problem_resolved' => $_POST['input_problem_resolved'],
                'search' => $_REQUEST['search'],
                'add' => $_REQUEST['add'],
            ];

        return $input_parameters;
    }

    // Add new problem if submitted
    function add_problem($input_parameters) {

        // Connect to database
        $database_connection = make_database_connection();

        // Make sql statement template
        $query_template = "INSERT INTO problem_data (category_id, status_id, problem_reported, problem_resolved, problem_description) VALUES (%d, %d, '%s', '%s', '%s')";

        // Place parameters into template
        $database_query = sprintf($query_template,

            $input_parameters['category_id'],
            $input_parameters['status_id'],
            $input_parameters['problem_reported'],
            $input_parameters['problem_resolved'],
            $input_parameters['problem_description']
        );

        // Execute insert statement
        if (!mysqli_query($database_connection, $database_query)) {

            // Handle any errors
            throw new Exception("Error: Problem not added!");
        }
    }

function get_table_data($input_parameters) {

        // Make basic sql statement
        $database_query = "SELECT pd.problem_id, pd.problem_description, pd.problem_reported, pd.problem_resolved, pc.category_description, ps.status_description FROM problem_data AS pd INNER JOIN problem_category AS pc ON pc.category_id = pd.category_id INNER JOIN problem_status AS ps ON ps.status_id = pd.status_id";

        // Append any selected search parameters
        if ($input_parameters['search'] == "Search Problems") {

            // Clear array
            $query_parameters = [];

            if (!empty($input_parameters['problem_description'])) {

                $query_parameters[] = "pd.problem_description LIKE '%{$input_parameters['problem_description']}%'";
            }

            if (!empty($input_parameters['category_id'])) {

                $query_parameters[] = sprintf("pc.category_id = %d", $input_parameters['category_id']);
            }

            if (!empty($input_parameters['status_id'])) {

                $query_parameters[] = sprintf("ps.status_id = %d", $input_parameters['status_id']);
            }

            if (!empty($input_parameters['problem_reported'])) {

                $query_parameters[] = sprintf("pd.problem_reported >= '%s'", $input_parameters['problem_reported']);
            }

            if (!empty($input_parameters['problem_resolved'])) {

                $query_parameters[] = sprintf("pd.problem_resolved <= '%s'", $input_parameters['problem_resolved']);
            }

            if (!empty($query_parameters)) {

                $clause_structure = " WHERE " . implode(' AND ', $query_parameters);
                $database_query .= $clause_structure;
            }
        }

        // Fetch data from database, using the same connection as the other functions
        $database_connection = make_database_connection();
        $query_result = $database_connection->query($database_query);

        // Handle any errors
        if (empty($query_result)) {
            throw new Exception("Error: " . $database_connection->error);
        }

        // Clear array
        $record_list = [];

        // Assign query records to array
        while($current_record = mysqli_fetch_array($query_result)) {

            $record_list[] = $current_record;
        }

        // Write entry to error log
        error_log("Found: " . count($record_list) . " records for $database_query\n" . print_r($record_list, true));

        return $record_list;
    }

    // Render table
    function render_table($table_data) {

        // Make table template
        $table_template = <<<EOQ

        <table border='1'>

            <tr>

                <th>Description</th>
                <th>Category</th>
                <th>Status</th>
                <th>Reported</th>
                <th>Resolved</th>

            </tr>

            /* row_data */

        </table>

EOQ;

        $row_template = <<<EOQ

            <tr>

                <td><a href = "problemupdatetool_details.html?id=">%d %s</a></td>
                <td>%s</td>
                <td>%s</td>
                <td>%s</td>
                <td>%s</td>

            </tr>

EOQ;

        // Clear array
        $row_list = [];

        // Place records into array with data type formatting
        foreach ($table_data as $current_record) {

            $row_list[] = sprintf($row_template,

                $current_record['problem_id'],
                $current_record['problem_description'],
                $current_record['category_description'],
                $current_record['status_description'],
                $current_record['problem_reported'],
                $current_record['problem_resolved']
            );
        }

        // Make table row html
        $row_html   = implode("\n", $row_list);

        // Make table html from row thml and table template
        $table_html = str_replace('/* row_data */', $row_html, $table_template);

        return $table_html;
    }

    // Connect to database
    function make_database_connection() {

        // Reset database connection to clear cache
        $database_connection = null;

        if (is_null($database_connection)) {

            $database_connection = new mysqli("localhost", "root", "lamemysqlpass1", "problem");

            // Handle any errors
            if ($database_connection->connect_error) {

                throw new Exception("Error: " . $database_connection->connect_error);
            }
        }

        return $database_connection;
    }
?>
