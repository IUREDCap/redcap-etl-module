<?php
require_once APP_PATH_DOCROOT . 'Config/init_global.php';

# THIS PAGE WAS AUTO-GENERATED

$htmlPage = new HtmlPage();
$htmlPage->PrintHeaderExt();
?>
<div style="text-align:right;float:right;">
    <img src="<?php echo APP_PATH_IMAGES."redcap-logo.png"; ?>" alt="REDCap"/>
</div>
<?php // phpcs:disable ?>
<h1>REDCap-ETL Transformation Rules</h1>
<p>The transformation rules specify how the records in REDCap are transformed
into records in your database.</p>
<h2>Simple Transformation Rules Example</h2>
<p>This is a simple example with a single database table.
For this example, the project is non-longitudinal and
has one form called &quot;Registration&quot;.</p>
<p><strong>REDCap Data</strong></p>
<table class="dataTable">
<thead>
<tr>
<th style="text-align: right;">record_id</th>
<th>first_name</th>
<th>last_name</th>
<th>dob</th>
<th style="text-align: right;">registration_complete</th>
</tr>
</thead>
<tbody>
<tr>
<td style="text-align: right;">1001</td>
<td>Anahi</td>
<td>Gislason</td>
<td>08-27-1973</td>
<td style="text-align: right;">2</td>
</tr>
<tr>
<td style="text-align: right;">1002</td>
<td>Marianne</td>
<td>Crona</td>
<td>06-18-1958</td>
<td style="text-align: right;">2</td>
</tr>
<tr>
<td style="text-align: right;">1003</td>
<td>Ryann</td>
<td>Tillman</td>
<td>08-28-1967</td>
<td style="text-align: right;">2</td>
</tr>
</tbody>
</table>
<p><strong>Transformation Rules</strong></p>
<pre><code>TABLE,registration,registration_id,ROOT
FIELD,first_name,string
FIELD,last_name,string
FIELD,dob,date,birthdate</code></pre>
<p><strong>Database Table</strong></p>
<p><strong>registration</strong></p>
<table class="dataTable">
<thead>
<tr>
<th style="text-align: right;">registration_id</th>
<th>record_id</th>
<th>first_name</th>
<th>last_name</th>
<th>birthdate</th>
</tr>
</thead>
<tbody>
<tr>
<td style="text-align: right;">1</td>
<td>1001</td>
<td>Anahi</td>
<td>Gislason</td>
<td>1973-08-27</td>
</tr>
<tr>
<td style="text-align: right;">2</td>
<td>1002</td>
<td>Marianne</td>
<td>Crona</td>
<td>1958-06-18</td>
</tr>
<tr>
<td style="text-align: right;">3</td>
<td>1003</td>
<td>Ryann</td>
<td>Tillman</td>
<td>1967-08-28</td>
</tr>
</tbody>
</table>
<p>In this example:</p>
<ul>
<li>The database table name is specified as <strong>registration</strong></li>
<li>The database table is specified with a <strong>&quot;rows type&quot;</strong> of ROOT, because the rows of data have
a one-to-one mapping to record IDs, i.e., each study participant has
one first name, one last name, and one birthdate.</li>
<li>The database field <strong>registration_id</strong> (specified in the TABLE command)
is created automatically as an auto-incremented synthetic key</li>
<li>The database field <strong>record_id</strong> represents the REDCap record ID, and is
created automatically in the database for all tables by REDCap-ETL</li>
<li>The database fields <strong>record_id</strong>, <strong>first_name</strong> and <strong>last_name</strong>
match the REDCap fields.</li>
<li>The REDCap field <strong>dob</strong> with type <strong>date</strong>, was renamed to <strong>birthdate</strong> in the database</li>
<li>The <strong>birthdate</strong> database field has Y-M-D format, which is what REDCap
returns (even though the field was defined as having M-D-Y format in REDCap)</li>
<li>No transformation rule was defined for the REDCap <strong>registration_complete</strong> field,
so it does not show up in the database. You are not required to specify a
rule for every field, so you can specify rules for only those fields that
you are interested in.</li>
</ul>
<hr />
<h2>Transformation Rules Syntax</h2>
<p>Transformation rules consists of one or more TABLE statements, where each TABLE statement is followed by zero or more FIELD statements that specify what REDCap fields are
stored in the table. Each statement needs to be on its own line.</p>
<pre><code>TABLE, &lt;table_name&gt;, &lt;parent_table|primary_key_name&gt;, &lt;rows_type&gt;
FIELD, &lt;field_name&gt;, &lt;field_type&gt;[, &lt;database_field_name&gt;]
...
FIELD, &lt;field_name&gt;, &lt;field_type&gt;[, &lt;database_field_name&gt;]

TABLE, &lt;table_name&gt;, &lt;parent_table|primary_key_name&gt;, &lt;rows_type&gt;
FIELD, &lt;field_name&gt;, &lt;field_type&gt;[, &lt;database_field_name&gt;]
...</code></pre>
<p>A table statement with rows type ROOT that has no fields following it will generate a table that contains only
a synthetic primary key field and a record ID field.</p>
<p>The transformation rules language is line-oriented, and each line has a
comma-separated value (CSV) syntax. This allows the transformation rules to be
edited as a spreadsheet, as long as it is saved in CSV format. Editing this way
eliminates the need to enter field separators (commas),
and automatically aligns field mappings horizontally, which makes them easier to read.</p>
<h4>Comments and Ignored Lines</h4>
<p>The following lines are ignored by ETL processing:</p>
<ul>
<li>Comment lines - lines where the first (non-space) character is a #</li>
<li>Blank lines</li>
<li>Lines with <em>all</em> blank fields (i.e., only commas, or commas and spaces)</li>
</ul>
<h4>TABLE Statements</h4>
<p>Table statements specify the tables that should be generated in your database.</p>
<pre><code>TABLE, &lt;table_name&gt;, &lt;parent_table|primary_key_name&gt;, &lt;rows_type&gt;</code></pre>
<p>Note: if the table is a root table, it has no parent table, and the field after the table name is the name to use for the table's (synthetic) primary key:</p>
<pre><code>TABLE, &lt;table_name&gt;, &lt;primary_key_name&gt;, ROOT</code></pre>
<p>For non-root tables, the field after the table name is the name of its parent table.</p>
<pre><code>TABLE, &lt;table_name&gt;, &lt;parent_table&gt;, &lt;rows_type&gt;</code></pre>
<p>The <code>&lt;rows_type&gt;</code> indicates the following things about the table:</p>
<ol>
<li>If the table is a root table or a child table of another table.</li>
<li>What data rows from REDCap will be stored in the table.</li>
<li>What identifier/key fields the rows of the database table will contain.</li>
</ol>
<p>The possible <code>&lt;rows_type&gt;</code> values are shown in the table below:</p>
<table class="dataTable">
    <thead>
    <tr>
      <th>Rows Type</th><th>Description</th>
    </tr>
    </thead>
    <tbody>
    <tr>
      <td>ROOT</td>
      <td>
      This indicates that the table is a root table (it has no parent table) and
      is typically used for a table that stores REDCap fields that have
      a one-to-one relationship with
      the REDCap record ID, for example: first name, last name, birthdate.
      </td>
    </tr>
    <tr>
      <td>EVENTS</td>
      <td>
      If this rows type is specified, only REDCap values that are in rows that are "standard"
      events (i.e., from non-repeating forms that are in non-repeating events) will
      be stored in the database table. Since the same form can be in multiple (non-repeating)
      events, in general, the rows in the database table will have a many-to-one
      relationship with the record ID. For example, you might have a field
      "total_cholesterol" in events "Initial Visit" and "Final Visit", so there would
      be 2 "total_cholesterol" values per record ID.
      </td>
    </tr>
    <tr>
      <td>REPEATING_EVENTS</td>
      <td>
      This rows type indicates that only REDCap values from rows that are in
      repeating events will be stored in the database table.
      </td>
    </tr>
    <tr>
      <td>REPEATING_INSTRUMENTS</td>
      <td>
      This rows type indicates that only REDCap values that are in
      repeating instruments will be stored in the database table.
      </td>
    </tr>
    <tr>
      <td>&lt;suffixes&gt;</td>
      <td>
      This is typically used for a table that stores related REDCap fields that have
      the same prefix, but different suffixes. For example, you might specify
      suffixes of "1;2;3" for fields "heart_rate1",
      "heart_rate2" and "heart_rate3" that represent different heart rate
      measurements of a participant, and would represent a many-to-one relationship
      of heart rate to the the record ID field.
      </td>
    </tr>
    <tr>
      <td>EVENTS:&lt;suffixes&gt;</td>
      <td>
      This is typically used for a table that stores related REDCap fields that have
      the same prefix, but different suffixes, that occur in multiple events in
      a longitudinal study. For example, you might specify
      suffixes of "1;2;3" for fields "heart_rate1",
      "heart_rate2" and "heart_rate3" that represent different heart rate
      measurements of a participant, and are in events "Initial Visit" and "Final Visit".
      The events would represent a many-to-one relationship with the record ID, and
      heart rate field would represent a many-to-one relationship with
      the event that contained them.
      </td>
    </tr>
    </tbody>
</table>
<p><strong>&amp; operator for rows type</strong></p>
<p>For longitudinal studies, the three rows type <code>EVENTS</code>, <code>REPEATING_INSTRUMENTS</code> and <code>REPEATING_EVENTS</code> can be combined together using the <code>&amp;</code> operator, for example:</p>
<pre><code>TABLE, visits, enrollment, EVENTS &amp; REPEATING_EVENTS</code></pre>
<p><strong>Suffixes</strong></p>
<p><code>&lt;suffixes&gt;</code> is in the format</p>
<pre><code>&lt;suffix1&gt;; &lt;suffix2&gt;; ...</code></pre>
<p>for example:</p>
<pre><code>1;2;3;4
a;b;c
first; second; third</code></pre>
<p><strong>Identifier/key fields</strong></p>
<p>REDCap-ETL will automatically create various identifier and key fields in
each database table:</p>
<ul>
<li><strong><code>&lt;primary_key&gt;</code></strong> - a numeric synthetic key  (created for all tables)</li>
<li><strong><code>&lt;foreign_key&gt;</code></strong> - a numeric foreign key that references the primary key of
the table's parent table.
This field is created for all tables with a rows type other than <code>ROOT</code>. </li>
<li><strong><code>&lt;record_id&gt;</code></strong> - the record ID from REDCap (created for all tables)</li>
<li><strong><code>redcap_event_name</code></strong> - the REDCap unique event name for the data record in REDCap. This is
only created if the REDCap study is longitudinal, and the table's rows type is one
of the following: <code>EVENTS</code>, <code>REPEATING_EVENTS</code>, <code>REPEATING_INSTRUMENTS</code>,
<code>EVENTS:&lt;suffixes&gt;</code></li>
<li><strong><code>redcap_repeat_instrument</code></strong> - the REDCap instrument name for the data record in REDCap. This
is only created for tables with the rows type <code>REPEATING_INSTRUMENTS</code></li>
<li><strong><code>redcap_repeat_instance</code></strong> - the REDCap instance value for the data record in REDCap.
This field is only created for tables with rows types <code>REPEATING_EVENTS</code> or
<code>REPEATING_INSTRUMENTS</code>.</li>
<li><strong><code>redcap_suffix</code></strong> - this field contains the suffix value for the record. This field is
only created for tables that have a rows type of <code>&lt;suffixes&gt;</code> or <code>EVENTS:&lt;suffixes&gt;</code>.</li>
</ul>
<h4>FIELD Statements</h4>
<p>Field statements specify the fields that are generated in the tables in your database.</p>
<pre><code>FIELD, &lt;field_name&gt;, &lt;field_type&gt;[, &lt;database_field_name&gt;]</code></pre>
<p><strong><code>&lt;field_name&gt;</code></strong> is the name of the field in REDCap.</p>
<ul>
<li>If <strong><code>&lt;database_field_name&gt;</code></strong> is not set, <strong><code>&lt;field_name&gt;</code></strong> will also be the name
of the field in the database where the extracted data are loaded</li>
<li>If <strong><code>&lt;database_field_name&gt;</code></strong> is set, then it will be used as
the name of the field in the database where the extracted data are loaded</li>
</ul>
<p><strong><code>&lt;field_type&gt;</code></strong> can be one of the REDCap-ETL types in the table below that shows
the database types used to store the different REDCap-ETL types.</p>
<table class="dataTable">
<thead>
<tr>
<th>REDCap-ETL Type</th>
<th>MySQL Type</th>
<th>CSV (Spreadsheet) Type</th>
</tr>
</thead>
<tbody>
<tr>
<td>int</td>
<td>int</td>
<td>number</td>
</tr>
<tr>
<td>float</td>
<td>float</td>
<td>number</td>
</tr>
<tr>
<td>char(<em>size</em>)</td>
<td>char(<em>size</em>)</td>
<td>text</td>
</tr>
<tr>
<td>varchar(<em>size</em>)</td>
<td>varchar(<em>size</em>)</td>
<td>text</td>
</tr>
<tr>
<td>string</td>
<td>text</td>
<td>text</td>
</tr>
<tr>
<td>date</td>
<td>date</td>
<td>datetime</td>
</tr>
<tr>
<td>datetime</td>
<td>datetime</td>
<td>datetime</td>
</tr>
<tr>
<td>checkbox</td>
<td>int</td>
<td>number</td>
</tr>
</tbody>
</table>
<p>NOTE: <code>TABLE</code>, <code>FIELD</code>, <code>&lt;rows_type&gt;</code>, and <code>&lt;field_type&gt;</code>; are all case sensitive. So, for example, <code>TABLE</code>, <code>FIELD</code>, <code>ROOT</code>, and <code>EVENTS</code> must be uppercase, and <code>int</code>, <code>float</code>, <code>string</code>, <code>date</code>, and <code>checkbox</code> must be lowercase.</p>
<hr />
<h2>Transformation Rules Examples</h2>
<h3>Events Example</h3>
<p>In this example, the REDCap project is a longitudinal project with a registration form and a visit form.
The visit form is used by 3 events: Visit1, Visit2 and Visit3.</p>
<p><strong>REDCap Data</strong></p>
<table class="dataTable">
<thead>
<tr>
<th>record_id</th>
<th>redcap_event_name</th>
<th>first_name</th>
<th>last_name</th>
<th>dob</th>
<th style="text-align: right;">registration_complete</th>
<th style="text-align: right;">weight</th>
<th style="text-align: right;">height</th>
<th style="text-align: right;">visit_complete</th>
</tr>
</thead>
<tbody>
<tr>
<td>1001</td>
<td>registration_arm_1</td>
<td>Anahi</td>
<td>Gislason</td>
<td>8/27/1973</td>
<td style="text-align: right;">2</td>
<td style="text-align: right;"></td>
<td style="text-align: right;"></td>
<td style="text-align: right;"></td>
</tr>
<tr>
<td>1001</td>
<td>visit1_arm_1</td>
<td></td>
<td></td>
<td></td>
<td style="text-align: right;"></td>
<td style="text-align: right;">90</td>
<td style="text-align: right;">1.7</td>
<td style="text-align: right;">2</td>
</tr>
<tr>
<td>1001</td>
<td>visit2_arm_1</td>
<td></td>
<td></td>
<td></td>
<td style="text-align: right;"></td>
<td style="text-align: right;">91</td>
<td style="text-align: right;">1.7</td>
<td style="text-align: right;">2</td>
</tr>
<tr>
<td>1001</td>
<td>visit3_arm_1</td>
<td></td>
<td></td>
<td></td>
<td style="text-align: right;"></td>
<td style="text-align: right;">92</td>
<td style="text-align: right;">1.7</td>
<td style="text-align: right;">2</td>
</tr>
<tr>
<td>1002</td>
<td>registration_arm_1</td>
<td>Marianne</td>
<td>Crona</td>
<td>6/18/1958</td>
<td style="text-align: right;">2</td>
<td style="text-align: right;"></td>
<td style="text-align: right;"></td>
<td style="text-align: right;"></td>
</tr>
<tr>
<td>1002</td>
<td>visit1_arm_1</td>
<td></td>
<td></td>
<td></td>
<td style="text-align: right;"></td>
<td style="text-align: right;">88</td>
<td style="text-align: right;">1.8</td>
<td style="text-align: right;">2</td>
</tr>
<tr>
<td>1002</td>
<td>visit2_arm_1</td>
<td></td>
<td></td>
<td></td>
<td style="text-align: right;"></td>
<td style="text-align: right;">88</td>
<td style="text-align: right;">1.8</td>
<td style="text-align: right;">2</td>
</tr>
<tr>
<td>1002</td>
<td>visit3_arm_1</td>
<td></td>
<td></td>
<td></td>
<td style="text-align: right;"></td>
<td style="text-align: right;">87</td>
<td style="text-align: right;">1.8</td>
<td style="text-align: right;">2</td>
</tr>
<tr>
<td>1003</td>
<td>registration_arm_1</td>
<td>Ryann</td>
<td>Tillman</td>
<td>8/28/1967</td>
<td style="text-align: right;">2</td>
<td style="text-align: right;"></td>
<td style="text-align: right;"></td>
<td style="text-align: right;"></td>
</tr>
<tr>
<td>1003</td>
<td>visit1_arm_1</td>
<td></td>
<td></td>
<td></td>
<td style="text-align: right;"></td>
<td style="text-align: right;">100</td>
<td style="text-align: right;">1.9</td>
<td style="text-align: right;">2</td>
</tr>
<tr>
<td>1003</td>
<td>visit2_arm_1</td>
<td></td>
<td></td>
<td></td>
<td style="text-align: right;"></td>
<td style="text-align: right;">102</td>
<td style="text-align: right;">1.9</td>
<td style="text-align: right;">2</td>
</tr>
<tr>
<td>1003</td>
<td>visit3_arm_1</td>
<td></td>
<td></td>
<td></td>
<td style="text-align: right;"></td>
<td style="text-align: right;">105</td>
<td style="text-align: right;">1.9</td>
<td style="text-align: right;">2</td>
</tr>
</tbody>
</table>
<p><strong>Transformation Rules</strong></p>
<pre><code>TABLE,registration,registration_id,ROOT
FIELD,record_id,string
FIELD,first_name,string
FIELD,last_name,string
FIELD,dob,date

TABLE,visit,registration,EVENTS
FIELD,weight,string
FIELD,height,string</code></pre>
<p><strong>Database Tables</strong></p>
<p><strong>registration</strong></p>
<table class="dataTable">
<thead>
<tr>
<th style="text-align: right;">registration_id</th>
<th>record_id</th>
<th>first_name</th>
<th>last_name</th>
<th>birthdate</th>
</tr>
</thead>
<tbody>
<tr>
<td style="text-align: right;">1</td>
<td>1001</td>
<td>Anahi</td>
<td>Gislason</td>
<td>1973-08-27</td>
</tr>
<tr>
<td style="text-align: right;">2</td>
<td>1002</td>
<td>Marianne</td>
<td>Crona</td>
<td>1958-06-18</td>
</tr>
<tr>
<td style="text-align: right;">3</td>
<td>1003</td>
<td>Ryann</td>
<td>Tillman</td>
<td>1967-08-28</td>
</tr>
</tbody>
</table>
<p><strong>visits</strong></p>
<table class="dataTable">
<thead>
<tr>
<th style="text-align: right;">visit_id</th>
<th style="text-align: right;">registration_id</th>
<th>record_id</th>
<th>redcap_event_name</th>
<th style="text-align: right;">weight</th>
<th style="text-align: right;">height</th>
</tr>
</thead>
<tbody>
<tr>
<td style="text-align: right;">1</td>
<td style="text-align: right;">1</td>
<td>1001</td>
<td>visit1_arm_1</td>
<td style="text-align: right;">90</td>
<td style="text-align: right;">1.7</td>
</tr>
<tr>
<td style="text-align: right;">2</td>
<td style="text-align: right;">1</td>
<td>1001</td>
<td>visit2_arm_1</td>
<td style="text-align: right;">91</td>
<td style="text-align: right;">1.7</td>
</tr>
<tr>
<td style="text-align: right;">3</td>
<td style="text-align: right;">1</td>
<td>1001</td>
<td>visit3_arm_1</td>
<td style="text-align: right;">92</td>
<td style="text-align: right;">1.7</td>
</tr>
<tr>
<td style="text-align: right;">4</td>
<td style="text-align: right;">2</td>
<td>1002</td>
<td>visit1_arm_1</td>
<td style="text-align: right;">88</td>
<td style="text-align: right;">1.8</td>
</tr>
<tr>
<td style="text-align: right;">5</td>
<td style="text-align: right;">2</td>
<td>1002</td>
<td>visit2_arm_1</td>
<td style="text-align: right;">88</td>
<td style="text-align: right;">1.8</td>
</tr>
<tr>
<td style="text-align: right;">6</td>
<td style="text-align: right;">2</td>
<td>1002</td>
<td>visit3_arm_1</td>
<td style="text-align: right;">87</td>
<td style="text-align: right;">1.8</td>
</tr>
<tr>
<td style="text-align: right;">7</td>
<td style="text-align: right;">3</td>
<td>1003</td>
<td>visit1_arm_1</td>
<td style="text-align: right;">100</td>
<td style="text-align: right;">1.9</td>
</tr>
<tr>
<td style="text-align: right;">8</td>
<td style="text-align: right;">3</td>
<td>1003</td>
<td>visit2_arm_1</td>
<td style="text-align: right;">102</td>
<td style="text-align: right;">1.9</td>
</tr>
<tr>
<td style="text-align: right;">9</td>
<td style="text-align: right;">3</td>
<td>1003</td>
<td>visit3_arm_1</td>
<td style="text-align: right;">105</td>
<td style="text-align: right;">1.9</td>
</tr>
</tbody>
</table>
<p>For the <strong>visits</strong> table:</p>
<ul>
<li><strong>visit_id</strong> is the synthetic primary key automatically generated by REDCap-ETL</li>
<li><strong>registration_id</strong> is the foreign key automatically generated by REDCap-ETL that points
to the parent table <strong>registration</strong></li>
</ul>
<hr />
<h3>Complex Example</h3>
<p><strong>REDCap Data</strong></p>
<table class="dataTable">
<thead>
<tr>
<th>Event</th>
<th>Variable</th>
<th>Record1</th>
<th>Record2</th>
<th>Record3</th>
</tr>
</thead>
<tbody>
<tr>
<td>Initial</td>
<td>record</td>
<td>1</td>
<td>2</td>
<td>3</td>
</tr>
<tr>
<td>Initial</td>
<td>var1</td>
<td>Joe</td>
<td>Jane</td>
<td>Rob</td>
</tr>
<tr>
<td>Initial</td>
<td>var2</td>
<td>Smith</td>
<td>Doe</td>
<td>Smith</td>
</tr>
<tr>
<td></td>
<td></td>
<td></td>
<td></td>
<td></td>
</tr>
<tr>
<td>evA</td>
<td>var3</td>
<td>10</td>
<td>11</td>
<td>12</td>
</tr>
<tr>
<td>evA</td>
<td>var4</td>
<td>20</td>
<td>21</td>
<td>22</td>
</tr>
<tr>
<td>evA</td>
<td>var5a</td>
<td>1001</td>
<td>1021</td>
<td>1031</td>
</tr>
<tr>
<td>evA</td>
<td>var6a</td>
<td>2001</td>
<td>2021</td>
<td>2031</td>
</tr>
<tr>
<td>evA</td>
<td>var5b</td>
<td>1002</td>
<td>1022</td>
<td>1032</td>
</tr>
<tr>
<td>evA</td>
<td>var6b</td>
<td>2002</td>
<td>2022</td>
<td>2032</td>
</tr>
<tr>
<td>evA</td>
<td>var7</td>
<td>10,000</td>
<td>10,001</td>
<td>10,002</td>
</tr>
<tr>
<td>evA</td>
<td>var8a</td>
<td>red1</td>
<td>red2</td>
<td>red3</td>
</tr>
<tr>
<td>evA</td>
<td>var8b</td>
<td>green1</td>
<td>green2</td>
<td>green3</td>
</tr>
<tr>
<td></td>
<td></td>
<td></td>
<td></td>
<td></td>
</tr>
<tr>
<td>evB</td>
<td>var3</td>
<td>101</td>
<td>102</td>
<td>103</td>
</tr>
<tr>
<td>evB</td>
<td>var4</td>
<td>201</td>
<td>202</td>
<td>203</td>
</tr>
<tr>
<td>evB</td>
<td>var5a</td>
<td>3001</td>
<td>3021</td>
<td>3031</td>
</tr>
<tr>
<td>evB</td>
<td>var6a</td>
<td>4001</td>
<td>4021</td>
<td>4031</td>
</tr>
<tr>
<td>evB</td>
<td>var5b</td>
<td>3002</td>
<td>3022</td>
<td>3032</td>
</tr>
<tr>
<td>evB</td>
<td>var6b</td>
<td>4002</td>
<td>4022</td>
<td>4032</td>
</tr>
<tr>
<td>evB</td>
<td>var7</td>
<td>20,000</td>
<td>20,001</td>
<td>20,002</td>
</tr>
<tr>
<td>evB</td>
<td>var8a</td>
<td>blue1</td>
<td>blue2</td>
<td>blue3</td>
</tr>
<tr>
<td>evB</td>
<td>var8b</td>
<td>yellow1</td>
<td>yellow2</td>
<td>yellow3</td>
</tr>
</tbody>
</table>
<p><strong>Transformation Rules</strong></p>
<pre><code>TABLE,  Main, Main_id, ROOT
FIELD,  record, int
FIELD,  var1, string
FIELD,  var2, string

TABLE,  Second, Main, EVENTS
FIELD,  var3, int
FIELD,  var4, int

TABLE,  Third, Main, EVENTS
FIELD,  var7, int

TABLE,  Fourth, Third, a;b
FIELD,  var5, int
FIELD,  var6, int

TABLE,  Fifth, Main, EVENTS:a;b
FIELD,  var8, st</code></pre>
<p><strong>Database Tables</strong></p>
<p>NOTE: This only shows data transformed from REDCap record 1</p>
<p><strong>Main</strong></p>
<table class="dataTable">
<thead>
<tr>
<th>record_id</th>
<th>var1</th>
<th>var2</th>
</tr>
</thead>
<tbody>
<tr>
<td>1</td>
<td>Joe</td>
<td>Smith</td>
</tr>
</tbody>
</table>
<p><strong>Second</strong></p>
<table class="dataTable">
<thead>
<tr>
<th>second_id</th>
<th>record_id</th>
<th>redcap_event_name</th>
<th>var3</th>
<th>var4</th>
</tr>
</thead>
<tbody>
<tr>
<td>1</td>
<td>1</td>
<td>evA</td>
<td>10</td>
<td>20</td>
</tr>
<tr>
<td>2</td>
<td>1</td>
<td>evB</td>
<td>101</td>
<td>201</td>
</tr>
</tbody>
</table>
<p><strong>Third</strong></p>
<table class="dataTable">
<thead>
<tr>
<th>third_id</th>
<th>record_id</th>
<th>redcap_event_name</th>
<th>var7</th>
</tr>
</thead>
<tbody>
<tr>
<td>1</td>
<td>1</td>
<td>evA</td>
<td>10,000</td>
</tr>
<tr>
<td>2</td>
<td>1</td>
<td>evB</td>
<td>20,000</td>
</tr>
</tbody>
</table>
<p><strong>Fourth</strong></p>
<table class="dataTable">
<thead>
<tr>
<th>fourth_id</th>
<th>third_id</th>
<th>redcap_suffix</th>
<th>var5</th>
<th>var6</th>
</tr>
</thead>
<tbody>
<tr>
<td>1</td>
<td>1</td>
<td>a</td>
<td>1001</td>
<td>2001</td>
</tr>
<tr>
<td>2</td>
<td>1</td>
<td>b</td>
<td>1002</td>
<td>2002</td>
</tr>
<tr>
<td>3</td>
<td>2</td>
<td>a</td>
<td>3001</td>
<td>4001</td>
</tr>
<tr>
<td>4</td>
<td>2</td>
<td>b</td>
<td>3002</td>
<td>4002</td>
</tr>
</tbody>
</table>
<p><strong>Fifth</strong></p>
<table class="dataTable">
<thead>
<tr>
<th>fifth_id</th>
<th>record_id</th>
<th>redcap_event_name</th>
<th>redcap_suffix</th>
<th>var8</th>
</tr>
</thead>
<tbody>
<tr>
<td>1</td>
<td>1</td>
<td>evA</td>
<td>a</td>
<td>red1</td>
</tr>
<tr>
<td>2</td>
<td>1</td>
<td>evA</td>
<td>b</td>
<td>green1</td>
</tr>
<tr>
<td>3</td>
<td>1</td>
<td>evB</td>
<td>a</td>
<td>blue1</td>
</tr>
<tr>
<td>4</td>
<td>1</td>
<td>evB</td>
<td>b</td>
<td>yellow1</td>
</tr>
</tbody>
</table>
<ul>
<li>
<p>NOTE: In this example, var3/var4 need to be put in one table while var7 needs to be put in a different table, despite the fact that all three variables have the same 1-many relationship with var1/var2.</p>
</li>
<li>
<p>NOTE: This syntax assumes that Events will always be used to define 1-many relationships to the root parent table. Although we might envision a more complex situation in which events are named such that some events are considered children of other events, in practice that has not been done.</p>
</li>
<li>
<p>NOTE: This example does not include a situation in which a child table that uses suffixes has a parent table that also uses suffixes, but the transforming code can handle that situation.</p>
</li>
</ul><?php // phpcs:enable ?>

<style type="text/css">#footer { display: block; }</style>
<?php
$htmlPage->PrintFooterExt();
