@local @local_activitylibrary @core @javascript
Feature: As an admin I can hide an activity from the catalogue from its edit form

  Background:
    Given the following "courses" exist:
      | shortname | fullname |
      | C1        | Course 1 |
    And the following config values are set as admin:
      | config                | value |
      | enableactivitylibrary | 1     |
    And the following "activities" exist:
      | activity | name              | intro             | course | idnumber |
      | page     | Catalogue Activity | Visible on catalog | C1     | PAGE1    |

  Scenario: Hide an activity from the activity library using its module edit form
    Given I am on site homepage
    And I log in as "admin"
    And I navigate to activity library "Home" page
    And I wait until the page is ready
    And I should see "Catalogue Activity"
    And I am on the "Catalogue Activity" "page activity editing" page
    And I expand all fieldsets
    And I click on "Hide from catalogue" "checkbox"
    And I click on "Save and return to course" "button"
    And I navigate to activity library "Home" page
    When I wait until the page is ready
    Then I should not see "Catalogue Activity"

  Scenario: Student with a single hidden catalogue course sees an empty catalogue without invalid response
    Given the following "users" exist:
      | username | firstname | lastname | email                |
      | student1 | Test      | Student  | student1@example.com |
    And the following "course enrolments" exist:
      | user     | course | role    |
      | student1 | C1     | student |
    And I log in as "admin"
    And I hide course "Course 1" from the activity library catalogue
    And I log out
    And I log in as "student1"
    And I navigate to activity library "Home" page
    When I wait until the page is ready
    Then I should not see "Valeur retournée incorrecte détectée"
    And I should see "Aucun résultats ! Veuillez sélectionner d'autres valeurs pour les filtres."
