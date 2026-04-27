@mod @mod_eledialeitnerflow
Feature: Create and configure a LeitnerFlow activity
  As an editing teacher
  I need to create a LeitnerFlow activity with custom settings
  So that students can practise with spaced repetition

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Teacher   | One      | teacher1@example.com |
      | student1 | Student   | One      | student1@example.com |
    And the following "courses" exist:
      | fullname | shortname |
      | Course 1 | C1        |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | student1 | C1     | student        |
    And the following config values are set as admin:
      | texteditors | textarea |

  @javascript
  Scenario: Teacher creates a LeitnerFlow activity with default settings
    Given I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    When I add a "eledialeitnerflow" activity to course "Course 1" section "1"
    And I set the following fields to these values:
      | LeitnerFlow | Test LeitnerFlow |
    And I press "Save and return to course"
    Then I should see "Test LeitnerFlow" in the "region-main" "region"

  @javascript
  Scenario: Teacher configures Leitner system settings
    Given I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    When I add a "eledialeitnerflow" activity to course "Course 1" section "1"
    And I set the following fields to these values:
      | LeitnerFlow              | Leitner Custom    |
      | Number of boxes          | 5                 |
      | Correct answers required | 4                 |
      | On wrong answer          | Back one box      |
      | Card selection            | Mixed random      |
      | Questions per session    | 15                |
    And I press "Save and display"
    Then I should see "All participants overview"

  @javascript
  Scenario: Teacher configures display and feedback settings
    Given I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    When I add a "eledialeitnerflow" activity to course "Course 1" section "1"
    And I set the following fields to these values:
      | LeitnerFlow     | Leitner Display |
      | Feedback style  | Detailed        |
      | Card animation  | No              |
      | Animation delay | 2 s             |
      | Show intro tour | No              |
    And I press "Save and return to course"
    Then I should see "Leitner Display" in the "region-main" "region"

  @javascript
  Scenario: Teacher configures grading
    Given I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    When I add a "eledialeitnerflow" activity to course "Course 1" section "1"
    And I set the following fields to these values:
      | LeitnerFlow   | Leitner Graded      |
      | Grading       | % of cards learned  |
    And I press "Save and return to course"
    Then I should see "Leitner Graded" in the "region-main" "region"

  @javascript
  Scenario: Teacher can require a passing grade for completion
    Given I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    When I add a "eledialeitnerflow" activity to course "Course 1" section "1"
    And I set the following fields to these values:
      | LeitnerFlow   | Leitner Passing Grade |
      | Grading       | % of cards learned    |
      | Grade to pass | 50                    |
    And I expand all fieldsets
    And I click on "Add requirements" "radio"
    And I click on "Receive a grade" "checkbox"
    And I click on "Passing grade" "radio"
    And I press "Save and return to course"
    Then I should see "Leitner Passing Grade" in the "region-main" "region"
