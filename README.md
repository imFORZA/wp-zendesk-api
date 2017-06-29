# wp-zendesk-api

A WordPress php library for interacting with the [Zendesk API](https://developer.zendesk.com/rest_api/docs/core/introduction).

[![Code Climate](https://codeclimate.com/repos/57d2ff981a166e4f0f002da3/badges/b038d80d84f4657a9d10/gpa.svg)](https://codeclimate.com/repos/57d2ff981a166e4f0f002da3/feed)
[![Test Coverage](https://codeclimate.com/repos/57d2ff981a166e4f0f002da3/badges/b038d80d84f4657a9d10/coverage.svg)](https://codeclimate.com/repos/57d2ff981a166e4f0f002da3/coverage)
[![Issue Count](https://codeclimate.com/repos/57d2ff981a166e4f0f002da3/badges/b038d80d84f4657a9d10/issue_count.svg)](https://codeclimate.com/repos/57d2ff981a166e4f0f002da3/feed)


Example Usage:

		$zenapi = new Zendesk_Wordpress_API('imforza');
		if( ! $zenapi->authenticate('email@email.com', 'password')){
      error_log("hold up, auth error");
    }

		error_log(print_r( $zenapi->create_ticket("319 Main Fire", "The building is currently on fire"), true));
    error_log(print_r( $zenapi->list_tickets(), true));
    error_log(print_r( $zenapi->get_requests(), true));
