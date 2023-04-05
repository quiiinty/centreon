Feature:
  In order to check the shost templates
  As a logged in user
  I want to find host templates using api

  Background:
    Given a running cloud instance of Centreon Web API
    And the endpoints are described in Centreon Web API documentation

  Scenario: Host templates listing
    Given I am logged in
    And the following CLAPI import data:
    """
    HTPL;ADD;htpl-name-1;htpl-alias-1;;;;
    """

    When I send a GET request to '/api/latest/configuration/hosts/templates?search={"name":{"$lk":"htpl-%"}}'
    Then the response code should be "200"
    And the JSON should be equal to:
    """
    {
        "result": [
            {
              "id": 15,
              "name": "htpl-alias-1",
              "alias": "htpl-alias-1",
              "snmp_version": null,
              "snmp_community": null,
              "timezone_id": null,
              "severity_id": null,
              "check_command_id": null,
              "check_command_args": null,
              "check_timeperiod_id": null,
              "max_check_attempts": null,
              "normal_check_interval": null,
              "retry_check_interval": null,
              "is_active_check_enabled": 2,
              "isPassive_check_enabled": 2,
              "is_notification_enabled": 2,
              "notification_options": 31,
              "notification_interval": null,
              "notification_timeperiod_id": null,
              "add_inherited_contact_group": false,
              "add_inherited_contact": false,
              "first_notification_delay": null,
              "recovery_notification_delay": null,
              "acknowledgement_timeout": null,
              "is_freshness_checked": 2,
              "freshness_threshold": null,
              "is_flap_detection_enabled": 2,
              "low_flap_threshold": null,
              "high_flap_threshold": null,
              "is_event_handler_enabled": 2,
              "event_handler_command_id": null,
              "event_handler_command_args": null,
              "note_url": null,
              "note": null,
              "action_url": null,
              "icon_id": null,
              "icon_alternative": null,
              "comment": null,
              "is_activated": true,
              "is_locked": false
            }
        ],
        "meta": {
            "page": 1,
            "limit": 10,
            "search": {
              "$and": {"name": {"$lk": "htpl-%"}}
            },
            "sort_by": {},
            "total": 1
        }
    }
    """

  Scenario: Host template deletion
    Given I am logged in
    And the following CLAPI import data:
      """
      HTPL;ADD;htpl-name-1;htpl-alias-1;;;;
      """

    When I send a GET request to '/api/latest/configuration/hosts/templates?search={"name":{"$lk":"htpl-%"}}'
    Then the response code should be "200"
    And I store response values in:
      | name           | path         |
      | hostTemplateId | result[0].id |

    When I send a DELETE request to '/api/latest/configuration/hosts/templates/<hostTemplateId>'
    Then the response code should be "204"

    When I send a GET request to '/api/latest/configuration/hosts/templates?search={"name":{"$lk":"htpl-%"}}'
    Then the response code should be "200"
    And the json node "result" should have 0 elements