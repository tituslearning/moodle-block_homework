@block @block_homework
Feature: Add an instance of the homework block to a course
  In order to view the homework block in a course
  As a teacher
  I can add homework block to a course 
  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email | idnumber |
      | teacher1 | Teacher | 1 | teacher1@example.com | T1 |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1 | 0 |
    And the following "course enrolments" exist:
      | user | course | role |
      | teacher1 | C1 | editingteacher |

 @javascript
 Scenario: Add the block to a the course 
    And I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    Then I add the "Homework" block

