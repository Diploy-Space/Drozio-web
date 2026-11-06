<?php

return [

    'default_200' => [
        'identical_code' => 'default_200',
        'message' => 'Completed successfully',
        'http_response_code' => 200,
    ],

    'default_store_200' => [
        'identical_code' => 'default_store_200',
        'message' => 'Saved successfully',
        'http_response_code' => 200,
    ],

    'default_update_200' => [
        'identical_code' => 'default_update_200',
        'message' => 'Updated successfully',
        'http_response_code' => 200,
    ],

    'default_delete_200' => [
        'identical_code' => 'default_delete_200',
        'message' => 'Deleted successfully',
        'http_response_code' => 200,
    ],

    'data_fetched' => [
        'identical_code' => 'data_fetched_200',
        'message' => 'Loaded successfully',
        'http_response_code' => 200,
    ],

    'data_updated' => [
        'identical_code' => 'data_updated_200',
        'message' => 'Updated successfully',
        'http_response_code' => 200,
    ],

    'created_201' => [
        'identical_code' => 'created_201',
        'message' => 'Created successfully',
        'http_response_code' => 201,
    ],

    'deleted' => [
        'identical_code' => 'deleted_204',
        'message' => 'Deleted successfully',
        'http_response_code' => 204,
    ],

    'bad_request_400' => [
        'identical_code' => 'bad_request_400',
        'message' => 'We could not process that. Please try again',
        'http_response_code' => 400,
    ],

    'invalid_request' => [
        'identical_code' => 'invalid_request_400',
        'message' => 'That did not work. Please check and try again',
        'http_response_code' => 400,
    ],

    'unauthorized_401' => [
        'identical_code' => 'unauthorized_401',
        'message' => 'Please sign in to continue',
        'http_response_code' => 401,
    ],

    'invalid_credentials_401' => [
        'identical_code' => 'invalid_credentials_401',
        'message' => 'The credentials you entered are not correct',
        'http_response_code' => 401,
    ],

    'login_success' => [
        'identical_code' => 'login_success_200',
        'message' => 'Logged in successfully',
        'http_response_code' => 200,
    ],

    'registration_success' => [
        'identical_code' => 'registration_success_201',
        'message' => 'Registered successfully',
        'http_response_code' => 201,
    ],

    'verification_required' => [
        'identical_code' => 'verification_required_200',
        'message' => 'Verify your account to finish signing in',
        'http_response_code' => 200,
    ],

    'registration_verification_required' => [
        'identical_code' => 'registration_verification_required_201',
        'message' => 'Verify your account to finish signing up',
        'http_response_code' => 201,
    ],

    'phone_required' => [
        'identical_code' => 'phone_required_200',
        'message' => 'Add a phone number to finish signing in',
        'http_response_code' => 200,
    ],

    'registration_required' => [
        'identical_code' => 'registration_required_200',
        'message' => 'No account matches these details. Please register first',
        'http_response_code' => 200,
    ],

    'token_expired' => [
        'identical_code' => 'token_expired_401',
        'message' => 'Your session has expired. Please log in again',
        'http_response_code' => 401,
    ],

    'token_invalid' => [
        'identical_code' => 'token_invalid_401',
        'message' => 'Your session is not valid. Please log in again',
        'http_response_code' => 401,
    ],

    'token_not_provided' => [
        'identical_code' => 'token_not_provided_401',
        'message' => 'Please log in to continue',
        'http_response_code' => 401,
    ],

    'forbidden_403' => [
        'identical_code' => 'forbidden_403',
        'message' => 'You do not have permission to make this request',
        'http_response_code' => 403,
    ],

    'not_found_404' => [
        'identical_code' => 'not_found_404',
        'message' => 'We could not find what you were looking for',
        'http_response_code' => 404,
    ],

    'data_not_found' => [
        'identical_code' => 'data_not_found_404',
        'message' => 'Nothing was found',
        'http_response_code' => 404,
    ],

    'item_not_found' => [
        'identical_code' => 'item_not_found_404',
        'message' => 'This item was not found',
        'http_response_code' => 404,
    ],

    'already_exist' => [
        'identical_code' => 'already_exist_409',
        'message' => 'This already exists',
        'http_response_code' => 409,
    ],

    'unprocessable_entity_422' => [
        'identical_code' => 'unprocessable_entity_422',
        'message' => 'Please check the details you entered and try again',
        'http_response_code' => 422,
    ],

    'validation_failed' => [
        'identical_code' => 'validation_failed_422',
        'message' => 'Please check the details you entered and try again',
        'http_response_code' => 422,
    ],

    'too_many_requests_429' => [
        'identical_code' => 'too_many_requests_429',
        'message' => 'Too many attempts. Please try again later',
        'http_response_code' => 429,
    ],

    'server_error_500' => [
        'identical_code' => 'server_error_500',
        'message' => 'Something went wrong on our side',
        'http_response_code' => 500,
    ],

    'default_502' => [
        'identical_code' => 'bad_gateway_502',
        'message' => 'We could not complete that right now. Please try again',
        'http_response_code' => 502,
    ],

    'service_unavailable_503' => [
        'identical_code' => 'service_unavailable_503',
        'message' => 'This service is temporarily unavailable',
        'http_response_code' => 503,
    ],

    'maintenance_mode' => [
        'identical_code' => 'maintenance_mode_503',
        'message' => 'The service is down for maintenance. Please try again shortly',
        'http_response_code' => 503,
    ],

    'default_504' => [
        'identical_code' => 'gateway_timeout_504',
        'message' => 'That took too long. Please try again',
        'http_response_code' => 504,
    ],

    'address_updated' => [
        'identical_code' => 'address_updated_200',
        'message' => 'Address updated successfully',
        'http_response_code' => 200,
    ],

    'address_removed' => [
        'identical_code' => 'address_removed_200',
        'message' => 'Address removed successfully',
        'http_response_code' => 200,
    ],

    'notification_not_found' => [
        'identical_code' => 'notification_not_found_404',
        'message' => 'Notification not found',
        'http_response_code' => 404,
    ],

    'deal_not_found' => [
        'identical_code' => 'deal_not_found_404',
        'message' => 'Deal not found',
        'http_response_code' => 404,
    ],

    'profile_updated' => [
        'identical_code' => 'profile_updated_200',
        'message' => 'Profile updated successfully',
        'http_response_code' => 200,
    ],

    'device_token_updated' => [
        'identical_code' => 'device_token_updated_200',
        'message' => 'Notifications set up for this device',
        'http_response_code' => 200,
    ],

    'account_deleted' => [
        'identical_code' => 'account_deleted_200',
        'message' => 'Your account has been deleted',
        'http_response_code' => 200,
    ],

    'shop_not_found' => [
        'identical_code' => 'shop_not_found_404',
        'message' => 'Shop not found',
        'http_response_code' => 404,
    ],

    'clearance_sale_not_found' => [
        'identical_code' => 'clearance_sale_not_found_404',
        'message' => 'Clearance sale not found',
        'http_response_code' => 404,
    ],

    'chat_recipient_not_found' => [
        'identical_code' => 'chat_recipient_not_found_404',
        'message' => 'This chat recipient was not found',
        'http_response_code' => 404,
    ],

    'messages_seen' => [
        'identical_code' => 'messages_seen_200',
        'message' => 'Messages marked as seen',
        'http_response_code' => 200,
    ],

    'invalid_chat_type' => [
        'identical_code' => 'invalid_chat_type_422',
        'message' => 'Choose a valid chat type',
        'http_response_code' => 422,
    ],

    'verification_session_saved' => [
        'identical_code' => 'verification_session_saved_200',
        'message' => 'Verification saved',
        'http_response_code' => 200,
    ],

    'support_ticket_closed' => [
        'identical_code' => 'support_ticket_closed_200',
        'message' => 'Support ticket closed successfully',
        'http_response_code' => 200,
    ],

    'support_ticket_not_found' => [
        'identical_code' => 'support_ticket_not_found_404',
        'message' => 'Support ticket not found',
        'http_response_code' => 404,
    ],

    'logged_out' => [
        'identical_code' => 'logged_out_200',
        'message' => 'Logged out successfully',
        'http_response_code' => 200,
    ],

    'password_changed' => [
        'identical_code' => 'password_changed_200',
        'message' => 'Password changed successfully',
        'http_response_code' => 200,
    ],

    'otp_sent' => [
        'identical_code' => 'otp_sent_200',
        'message' => 'OTP sent successfully',
        'http_response_code' => 200,
    ],

    'otp_verified' => [
        'identical_code' => 'otp_verified_200',
        'message' => 'OTP verified successfully',
        'http_response_code' => 200,
    ],

    'identity_verified' => [
        'identical_code' => 'identity_verified_200',
        'message' => 'Verified successfully',
        'http_response_code' => 200,
    ],

    'customer_added_successfully' => [
        'identical_code' => 'customer_added_successfully_201',
        'message' => 'Customer added successfully',
        'http_response_code' => 201,
    ],

    'address_added' => [
        'identical_code' => 'address_added_201',
        'message' => 'Address added successfully',
        'http_response_code' => 201,
    ],

    'support_ticket_created' => [
        'identical_code' => 'support_ticket_created_201',
        'message' => 'Support ticket created successfully',
        'http_response_code' => 201,
    ],

    'support_ticket_replied' => [
        'identical_code' => 'support_ticket_replied_201',
        'message' => 'Support ticket reply sent',
        'http_response_code' => 201,
    ],

    'message_sent' => [
        'identical_code' => 'message_sent_201',
        'message' => 'Message sent successfully',
        'http_response_code' => 201,
    ],

    'email_verified' => [
        'identical_code' => 'email_verified_200',
        'message' => 'Email is successfully verified',
        'http_response_code' => 200,
    ],

    'phone_verified' => [
        'identical_code' => 'phone_verified_200',
        'message' => 'Phone number is successfully verified',
        'http_response_code' => 200,
    ],

    'coupon_deleted_successfully' => [
        'identical_code' => 'coupon_deleted_successfully_200',
        'message' => 'Coupon deleted successfully',
        'http_response_code' => 200,
    ],

    'coupon_status_updated_successfully' => [
        'identical_code' => 'coupon_status_updated_successfully_200',
        'message' => 'Coupon status updated successfully',
        'http_response_code' => 200,
    ],

    'default_payment_method_updated_successfully' => [
        'identical_code' => 'default_payment_method_updated_successfully_200',
        'message' => 'Default payment method updated successfully',
        'http_response_code' => 200,
    ],

    'delivery_man_deleted_successfully' => [
        'identical_code' => 'delivery_man_deleted_successfully_200',
        'message' => 'Delivery man deleted successfully',
        'http_response_code' => 200,
    ],

    'delivery_man_status_updated_successfully' => [
        'identical_code' => 'delivery_man_status_updated_successfully_200',
        'message' => 'Delivery man status updated successfully',
        'http_response_code' => 200,
    ],

    'emergency_contact_deleted_successfully' => [
        'identical_code' => 'emergency_contact_deleted_successfully_200',
        'message' => 'Emergency contact deleted successfully',
        'http_response_code' => 200,
    ],

    'emergency_contact_status_updated_successfully' => [
        'identical_code' => 'emergency_contact_status_updated_successfully_200',
        'message' => 'Emergency contact status updated successfully',
        'http_response_code' => 200,
    ],

    'meta_updated_successfully' => [
        'identical_code' => 'meta_updated_successfully_200',
        'message' => 'Meta information updated successfully',
        'http_response_code' => 200,
    ],

    'payment_information_deleted_successfully' => [
        'identical_code' => 'payment_information_deleted_successfully_200',
        'message' => 'Payment information deleted successfully',
        'http_response_code' => 200,
    ],

    'payment_method_status_updated_successfully' => [
        'identical_code' => 'payment_method_status_updated_successfully_200',
        'message' => 'Payment method status updated successfully',
        'http_response_code' => 200,
    ],

    'ai_generate_limit_reached' => [
        'identical_code' => 'ai_generate_limit_reached_403',
        'message' => 'AI generation limit reached for this account',
        'http_response_code' => 403,
    ],

    'ai_generation_incomplete' => [
        'identical_code' => 'ai_generation_incomplete_422',
        'message' => 'AI could not generate this section',
        'http_response_code' => 422,
    ],

    'ai_generation_failed' => [
        'identical_code' => 'ai_generation_failed_500',
        'message' => 'AI generation failed',
        'http_response_code' => 500,
    ],

    'ai_assistant_image_uploaded' => [
        'identical_code' => 'ai_assistant_image_uploaded_200',
        'message' => 'Image uploaded successfully',
        'http_response_code' => 200,
    ],

    'product_deleted_successfully' => [
        'identical_code' => 'product_deleted_successfully_200',
        'message' => 'Product deleted successfully',
        'http_response_code' => 200,
    ],

    'product_status_updated_successfully' => [
        'identical_code' => 'product_status_updated_successfully_200',
        'message' => 'Product status updated successfully',
        'http_response_code' => 200,
    ],

    'product_must_be_approved' => [
        'identical_code' => 'product_must_be_approved_422',
        'message' => 'Product must be approved before it can be turned on',
        'http_response_code' => 422,
    ],

    'shipping_cost_updated_successfully' => [
        'identical_code' => 'shipping_cost_updated_successfully_200',
        'message' => 'Shipping cost updated successfully',
        'http_response_code' => 200,
    ],

    'shipping_method_deleted_successfully' => [
        'identical_code' => 'shipping_method_deleted_successfully_200',
        'message' => 'Shipping method deleted successfully',
        'http_response_code' => 200,
    ],

    'shipping_method_status_updated_successfully' => [
        'identical_code' => 'shipping_method_status_updated_successfully_200',
        'message' => 'Shipping method status updated successfully',
        'http_response_code' => 200,
    ],

    'shop_information_updated_successfully' => [
        'identical_code' => 'shop_information_updated_successfully_200',
        'message' => 'Shop information updated successfully',
        'http_response_code' => 200,
    ],

    'withdraw_request_closed_successfully' => [
        'identical_code' => 'withdraw_request_closed_successfully_200',
        'message' => 'Withdraw request closed successfully',
        'http_response_code' => 200,
    ],

    'language_updated_successfully' => [
        'identical_code' => 'language_updated_successfully_200',
        'message' => 'Language updated successfully',
        'http_response_code' => 200,
    ],

    'shipping_type_updated_successfully' => [
        'identical_code' => 'shipping_type_updated_successfully_200',
        'message' => 'Shipping type updated successfully',
        'http_response_code' => 200,
    ],

    'shop_status_updated_successfully' => [
        'identical_code' => 'shop_status_updated_successfully_200',
        'message' => 'Shop status updated successfully',
        'http_response_code' => 200,
    ],

    'vacation_mode_updated_successfully' => [
        'identical_code' => 'vacation_mode_updated_successfully_200',
        'message' => 'Vacation mode updated successfully',
        'http_response_code' => 200,
    ],

    'all_stock_clearance_products_removed_successfull' => [
        'identical_code' => 'all_stock_clearance_products_removed_successfull_200',
        'message' => 'All stock clearance products removed successfully',
        'http_response_code' => 200,
    ],

    'coupon_updated_successfully' => [
        'identical_code' => 'coupon_updated_successfully_200',
        'message' => 'Coupon updated successfully',
        'http_response_code' => 200,
    ],

    'delivery_man_updated_successfully' => [
        'identical_code' => 'delivery_man_updated_successfully_200',
        'message' => 'Delivery man updated successfully',
        'http_response_code' => 200,
    ],

    'emergency_contact_updated_successfully' => [
        'identical_code' => 'emergency_contact_updated_successfully_200',
        'message' => 'Emergency contact updated successfully',
        'http_response_code' => 200,
    ],

    'payment_information_updated_successfully' => [
        'identical_code' => 'payment_information_updated_successfully_200',
        'message' => 'Payment information updated successfully',
        'http_response_code' => 200,
    ],

    'shipping_method_updated_successfully' => [
        'identical_code' => 'shipping_method_updated_successfully_200',
        'message' => 'Shipping method updated successfully',
        'http_response_code' => 200,
    ],

    'withdraw_request_updated_successfully' => [
        'identical_code' => 'withdraw_request_updated_successfully_200',
        'message' => 'Withdraw request updated successfully',
        'http_response_code' => 200,
    ],

    'amount_received_successfully' => [
        'identical_code' => 'amount_received_successfully_201',
        'message' => 'Amount received successfully',
        'http_response_code' => 201,
    ],

    'coupon_added_successfully' => [
        'identical_code' => 'coupon_added_successfully_201',
        'message' => 'Coupon added successfully',
        'http_response_code' => 201,
    ],

    'delivery_man_added_successfully' => [
        'identical_code' => 'delivery_man_added_successfully_201',
        'message' => 'Delivery man added successfully',
        'http_response_code' => 201,
    ],

    'emergency_contact_added_successfully' => [
        'identical_code' => 'emergency_contact_added_successfully_201',
        'message' => 'Emergency contact added successfully',
        'http_response_code' => 201,
    ],

    'payment_information_added_successfully' => [
        'identical_code' => 'payment_information_added_successfully_201',
        'message' => 'Payment information added successfully',
        'http_response_code' => 201,
    ],

    'shipping_method_added_successfully' => [
        'identical_code' => 'shipping_method_added_successfully_201',
        'message' => 'Shipping method added successfully',
        'http_response_code' => 201,
    ],

    'withdraw_request_sent_successfully' => [
        'identical_code' => 'withdraw_request_sent_successfully_201',
        'message' => 'Withdraw request sent successfully',
        'http_response_code' => 201,
    ],

    'your_shop_application_has_been_submitted_for_rev' => [
        'identical_code' => 'your_shop_application_has_been_submitted_for_rev_201',
        'message' => 'Your shop application has been submitted for review',
        'http_response_code' => 201,
    ],

    'coupon_not_found' => [
        'identical_code' => 'coupon_not_found_404',
        'message' => 'Coupon not found',
        'http_response_code' => 404,
    ],

    'delivery_man_not_found' => [
        'identical_code' => 'delivery_man_not_found_404',
        'message' => 'Delivery man not found',
        'http_response_code' => 404,
    ],

    'emergency_contact_not_found' => [
        'identical_code' => 'emergency_contact_not_found_404',
        'message' => 'Emergency contact not found',
        'http_response_code' => 404,
    ],

    'order_not_found' => [
        'identical_code' => 'order_not_found_404',
        'message' => 'Order not found',
        'http_response_code' => 404,
    ],

    'payment_method_not_found' => [
        'identical_code' => 'payment_method_not_found_404',
        'message' => 'Payment method not found',
        'http_response_code' => 404,
    ],

    'please_setup_the_configuration_first' => [
        'identical_code' => 'please_setup_the_configuration_first_404',
        'message' => 'Please setup the configuration first',
        'http_response_code' => 404,
    ],

    'product_not_found' => [
        'identical_code' => 'product_not_found_404',
        'message' => 'Product not found',
        'http_response_code' => 404,
    ],

    'product_faq_not_found' => [
        'identical_code' => 'product_faq_not_found_404',
        'message' => 'FAQ not found',
        'http_response_code' => 404,
    ],

    'product_faq_updated_successfully' => [
        'identical_code' => 'product_faq_updated_successfully_200',
        'message' => 'FAQ updated successfully',
        'http_response_code' => 200,
    ],

    'product_faq_status_updated_successfully' => [
        'identical_code' => 'product_faq_status_updated_successfully_200',
        'message' => 'FAQ status updated successfully',
        'http_response_code' => 200,
    ],

    'product_faq_deleted_successfully' => [
        'identical_code' => 'product_faq_deleted_successfully_200',
        'message' => 'FAQ deleted successfully',
        'http_response_code' => 200,
    ],

    'shipping_method_not_found' => [
        'identical_code' => 'shipping_method_not_found_404',
        'message' => 'Shipping method not found',
        'http_response_code' => 404,
    ],

    'stock_clearance_product_not_found' => [
        'identical_code' => 'stock_clearance_product_not_found_404',
        'message' => 'Stock clearance product not found',
        'http_response_code' => 404,
    ],

    'withdraw_request_not_found' => [
        'identical_code' => 'withdraw_request_not_found_404',
        'message' => 'Withdraw request not found',
        'http_response_code' => 404,
    ],

    'withdrawal_request_not_found' => [
        'identical_code' => 'withdrawal_request_not_found_404',
        'message' => 'Withdrawal request not found',
        'http_response_code' => 404,
    ],

    'currently_this_amount_is_not_withdrawable' => [
        'identical_code' => 'currently_this_amount_is_not_withdrawable_422',
        'message' => 'This amount cannot be withdrawn right now',
        'http_response_code' => 422,
    ],

    'one_of_the_selected_categories_is_not_available' => [
        'identical_code' => 'one_of_the_selected_categories_is_not_available_422',
        'message' => 'One of the selected categories is not available',
        'http_response_code' => 422,
    ],

    'receive_amount_cannot_be_more_than_cash_in_hand' => [
        'identical_code' => 'receive_amount_cannot_be_more_than_cash_in_hand_422',
        'message' => 'The amount received cannot be more than the cash in hand',
        'http_response_code' => 422,
    ],

    'this_withdrawal_request_could_not_be_settled' => [
        'identical_code' => 'this_withdrawal_request_could_not_be_settled_422',
        'message' => 'This withdrawal request could not be settled',
        'http_response_code' => 422,
    ],

    'this_withdrawal_request_has_already_been_decided' => [
        'identical_code' => 'this_withdrawal_request_has_already_been_decided_422',
        'message' => 'This withdrawal request has already been decided',
        'http_response_code' => 422,
    ],

    'your_products_unit_price_is_lower_then_offer_pri' => [
        'identical_code' => 'your_products_unit_price_is_lower_then_offer_pri_422',
        'message' => 'Your product unit price is lower than the offer price',
        'http_response_code' => 422,
    ],

    'discount_amount_required' => [
        'identical_code' => 'discount_amount_required_422',
        'message' => 'Please enter a discount amount',
        'http_response_code' => 422,
    ],

    'discount_amount_not_positive' => [
        'identical_code' => 'discount_amount_not_positive_422',
        'message' => 'Discount amount must be greater than zero',
        'http_response_code' => 422,
    ],

    'discount_percentage_out_of_range' => [
        'identical_code' => 'discount_percentage_out_of_range_422',
        'message' => 'Percentage discount must be between 1 and 100',
        'http_response_code' => 422,
    ],

    'discount_above_unit_price' => [
        'identical_code' => 'discount_above_unit_price_422',
        'message' => 'Discount cannot be more than the unit price',
        'http_response_code' => 422,
    ],

    'discount_managed_by_flat_setup' => [
        'identical_code' => 'discount_managed_by_flat_setup_422',
        'message' => 'The flat discount of the clearance sale setup applies to every product',
        'http_response_code' => 422,
    ],

    'discount_above_variation_price' => [
        'identical_code' => 'discount_above_variation_price_422',
        'message' => 'Discount cannot be more than any variation unit price',
        'http_response_code' => 422,
    ],

    'coupon_unusable' => [
        'identical_code' => 'coupon_unusable_422',
        'message' => 'This coupon code cannot be used',
        'http_response_code' => 422,
    ],

    'coupon_customer_mismatch' => [
        'identical_code' => 'coupon_customer_mismatch_422',
        'message' => 'This coupon does not belong to the selected customer',
        'http_response_code' => 422,
    ],

    'coupon_limit_reached' => [
        'identical_code' => 'coupon_limit_reached_422',
        'message' => 'This customer has already used this coupon the maximum number of times',
        'http_response_code' => 422,
    ],

    'coupon_min_purchase_not_met' => [
        'identical_code' => 'coupon_min_purchase_not_met_422',
        'message' => 'Your order does not meet the minimum purchase amount for this coupon',
        'http_response_code' => 422,
    ],

    'information_updated' => [
        'identical_code' => 'information_updated_200',
        'message' => 'Information updated successfully',
        'http_response_code' => 200,
    ],

    'order_not_assigned' => [
        'identical_code' => 'order_not_assigned_404',
        'message' => 'This order is not assigned to you',
        'http_response_code' => 404,
    ],

    'location_recorded' => [
        'identical_code' => 'location_recorded_201',
        'message' => 'Location recorded successfully',
        'http_response_code' => 201,
    ],

    'review_not_found' => [
        'identical_code' => 'review_not_found_404',
        'message' => 'This review was not found',
        'http_response_code' => 404,
    ],

    'review_updated' => [
        'identical_code' => 'review_updated_200',
        'message' => 'Review updated successfully',
        'http_response_code' => 200,
    ],

    'bank_information_updated' => [
        'identical_code' => 'bank_information_updated_200',
        'message' => 'Bank information updated successfully',
        'http_response_code' => 200,
    ],

    'online_status_on' => [
        'identical_code' => 'online_status_on_200',
        'message' => 'You are now online',
        'http_response_code' => 200,
    ],

    'online_status_off' => [
        'identical_code' => 'online_status_off_200',
        'message' => 'You are now offline',
        'http_response_code' => 200,
    ],

    'withdraw_request_sent' => [
        'identical_code' => 'withdraw_request_sent_201',
        'message' => 'Withdraw request sent successfully',
        'http_response_code' => 201,
    ],

    'notification_seen' => [
        'identical_code' => 'notification_seen_200',
        'message' => 'Notification marked as seen',
        'http_response_code' => 200,
    ],

    'not_implemented' => [
        'identical_code' => 'not_implemented_501',
        'message' => 'This feature is not available yet',
        'http_response_code' => 501,
    ],

    'credentials_mismatch' => [
        'identical_code' => 'credentials_mismatch_401',
        'message' => 'Your login details are not correct',
        'http_response_code' => 401,
    ],

    'guest_identity_required' => [
        'identical_code' => 'guest_identity_required_401',
        'message' => 'Please sign in to manage your addresses',
        'http_response_code' => 401,
    ],

    'provider_verification_failed' => [
        'identical_code' => 'provider_verification_failed_401',
        'message' => 'We could not verify that account. Please try again',
        'http_response_code' => 401,
    ],

    'vendor_approval_in_review' => [
        'identical_code' => 'vendor_approval_in_review_403',
        'message' => 'Your account is in review. Please wait for admin approval',
        'http_response_code' => 403,
    ],

    'vendor_approval_pending' => [
        'identical_code' => 'vendor_approval_pending_403',
        'message' => 'Your account is still under review',
        'http_response_code' => 403,
    ],

    'vendor_approval_revoked' => [
        'identical_code' => 'vendor_approval_revoked_403',
        'message' => 'This account is no longer approved',
        'http_response_code' => 403,
    ],

    'delivery_country_unavailable' => [
        'identical_code' => 'delivery_country_unavailable_403',
        'message' => 'Delivery is not available for this country',
        'http_response_code' => 403,
    ],

    'delivery_zip_unavailable' => [
        'identical_code' => 'delivery_zip_unavailable_403',
        'message' => 'Delivery is not available for this zip code area',
        'http_response_code' => 403,
    ],

    'otp_login_disabled' => [
        'identical_code' => 'otp_login_disabled_403',
        'message' => 'OTP login is turned off',
        'http_response_code' => 403,
    ],

    'social_login_disabled' => [
        'identical_code' => 'social_login_disabled_403',
        'message' => 'Social login is turned off',
        'http_response_code' => 403,
    ],

    'withdraw_amount_above_balance' => [
        'identical_code' => 'withdraw_amount_above_balance_403',
        'message' => 'The amount is more than your available balance',
        'http_response_code' => 403,
    ],

    'account_inactive' => [
        'identical_code' => 'account_inactive_403',
        'message' => 'This account is no longer active',
        'http_response_code' => 403,
    ],

    'login_method_disabled' => [
        'identical_code' => 'login_method_disabled_403',
        'message' => 'This login method is turned off',
        'http_response_code' => 403,
    ],

    'social_medium_disabled' => [
        'identical_code' => 'social_medium_disabled_403',
        'message' => 'This social login option is turned off',
        'http_response_code' => 403,
    ],

    'delivery_in_progress' => [
        'identical_code' => 'delivery_in_progress_403',
        'message' => 'You cannot go offline while an order is out for delivery',
        'http_response_code' => 403,
    ],

    'account_delete_ongoing_order' => [
        'identical_code' => 'account_delete_ongoing_order_403',
        'message' => 'You cannot delete your account while you have ongoing orders',
        'http_response_code' => 403,
    ],

    'account_delete_admin_commission_due' => [
        'identical_code' => 'account_delete_admin_commission_due_403',
        'message' => 'Please settle all your transactions with the admin first',
        'http_response_code' => 403,
    ],

    'account_delete_delivery_man_due' => [
        'identical_code' => 'account_delete_delivery_man_due_403',
        'message' => 'Please settle all your transactions with your delivery men first',
        'http_response_code' => 403,
    ],

    'account_delete_auction_active' => [
        'identical_code' => 'account_delete_auction_active_403',
        'message' => 'Your account cannot be deleted right now. You have an active auction in progress. Please wait until it has fully completed before trying again.',
        'http_response_code' => 403,
    ],

    'account_delete_auction_claim_unpaid' => [
        'identical_code' => 'account_delete_auction_claim_unpaid_403',
        'message' => 'Your account cannot be deleted right now. You have won an auction that has not been paid for yet. Please complete the claim payment first.',
        'http_response_code' => 403,
    ],

    'account_delete_auction_delivery_pending' => [
        'identical_code' => 'account_delete_auction_delivery_pending_403',
        'message' => 'Your account cannot be deleted right now. An auction item you won is still on the way. Please wait until the delivery has been completed.',
        'http_response_code' => 403,
    ],

    'account_delete_auction_fulfillment_pending' => [
        'identical_code' => 'account_delete_auction_fulfillment_pending_403',
        'message' => 'Your account cannot be deleted right now. An auction item you sold is still pending shipment. Please complete the delivery to the winner first.',
        'http_response_code' => 403,
    ],

    'account_delete_auction_commission_unsettled' => [
        'identical_code' => 'account_delete_auction_commission_unsettled_403',
        'message' => 'Your account cannot be deleted right now. Admin commission on one of your auctions is still unsettled. Please clear the commission before deleting your account.',
        'http_response_code' => 403,
    ],

    'account_delete_auction_commission_in_review' => [
        'identical_code' => 'account_delete_auction_commission_in_review_403',
        'message' => 'Your account cannot be deleted right now. You have an auction commission payment that is still being reviewed. Please try again once it has been processed.',
        'http_response_code' => 403,
    ],

    'account_delete_auction_withdraw_pending' => [
        'identical_code' => 'account_delete_auction_withdraw_pending_403',
        'message' => 'Your account cannot be deleted right now. You have an auction withdrawal request that is still pending. Please wait until it has been processed.',
        'http_response_code' => 403,
    ],

    'account_delete_auction_payment_in_review' => [
        'identical_code' => 'account_delete_auction_payment_in_review_403',
        'message' => 'Your account cannot be deleted right now. One of your auction payments is still being reviewed by the admin. Please try again once it has been confirmed.',
        'http_response_code' => 403,
    ],

    'device_account_not_found' => [
        'identical_code' => 'device_account_not_found_404',
        'message' => 'No account is linked to this device',
        'http_response_code' => 404,
    ],

    'account_not_found' => [
        'identical_code' => 'account_not_found_404',
        'message' => 'No account matches this phone number or email',
        'http_response_code' => 404,
    ],

    'seller_shop_not_found' => [
        'identical_code' => 'seller_shop_not_found_404',
        'message' => 'No shop is set up for this account yet',
        'http_response_code' => 404,
    ],

    'address_not_found' => [
        'identical_code' => 'address_not_found_404',
        'message' => 'This address was not found',
        'http_response_code' => 404,
    ],

    'signin_session_expired' => [
        'identical_code' => 'signin_session_expired_404',
        'message' => 'This sign-in session has expired. Please sign in again',
        'http_response_code' => 404,
    ],

    'email_already_registered' => [
        'identical_code' => 'email_already_registered_422',
        'message' => 'This email is already registered',
        'http_response_code' => 422,
    ],

    'phone_already_registered' => [
        'identical_code' => 'phone_already_registered_422',
        'message' => 'This phone number is already registered',
        'http_response_code' => 422,
    ],

    'otp_expired' => [
        'identical_code' => 'otp_expired_422',
        'message' => 'This OTP has expired. Please ask for a new one',
        'http_response_code' => 422,
    ],

    'otp_invalid' => [
        'identical_code' => 'otp_invalid_422',
        'message' => 'The OTP you entered is not correct',
        'http_response_code' => 422,
    ],

    'phone_reverification_required' => [
        'identical_code' => 'phone_reverification_required_422',
        'message' => 'Please verify your phone number again before creating the account',
        'http_response_code' => 422,
    ],

    'email_already_in_use' => [
        'identical_code' => 'email_already_in_use_422',
        'message' => 'This email is already in use. Please try another one',
        'http_response_code' => 422,
    ],

    'verification_session_mismatch' => [
        'identical_code' => 'verification_session_mismatch_422',
        'message' => 'This does not match the verification you started. Please try again',
        'http_response_code' => 422,
    ],

    'otp_resend_wait' => [
        'identical_code' => 'otp_resend_wait_429',
        'message' => 'An OTP was already sent. Please wait before asking for another one',
        'http_response_code' => 429,
    ],

    'login_attempts_blocked' => [
        'identical_code' => 'login_attempts_blocked_429',
        'message' => 'Too many failed attempts. Please try again later',
        'http_response_code' => 429,
    ],

    'otp_attempts_blocked' => [
        'identical_code' => 'otp_attempts_blocked_429',
        'message' => 'Too many wrong OTP attempts. Please try again later',
        'http_response_code' => 429,
    ],

    'firebase_not_configured' => [
        'identical_code' => 'firebase_not_configured_503',
        'message' => 'Phone verification is not available right now',
        'http_response_code' => 503,
    ],

    'map_service_unavailable' => [
        'identical_code' => 'map_service_unavailable_503',
        'message' => 'Map service is not available right now',
        'http_response_code' => 503,
    ],

    'otp_send_failed' => [
        'identical_code' => 'otp_send_failed_503',
        'message' => 'The OTP could not be sent right now. Please try again later',
        'http_response_code' => 503,
    ],

    'map_service_no_response' => [
        'identical_code' => 'map_service_no_response_503',
        'message' => 'The map service could not answer right now. Please try again',
        'http_response_code' => 503,
    ],

    'account_phone_missing' => [
        'identical_code' => 'account_phone_missing_503',
        'message' => 'This account has no phone number to send the OTP to',
        'http_response_code' => 503,
    ],

    'auction_tracking_url_updated' => [
        'identical_code' => 'auction_tracking_url_updated_200',
        'message' => 'Tracking url updated successfully',
        'http_response_code' => 200,
    ],

    'auction_address_updated' => [
        'identical_code' => 'auction_address_updated_200',
        'message' => 'Address updated successfully',
        'http_response_code' => 200,
    ],

    'auction_payment_status_updated' => [
        'identical_code' => 'auction_payment_status_updated_200',
        'message' => 'Payment status updated successfully',
        'http_response_code' => 200,
    ],

    'auction_delivery_status_updated' => [
        'identical_code' => 'auction_delivery_status_updated_200',
        'message' => 'Delivery status updated successfully',
        'http_response_code' => 200,
    ],

    'auction_canceled' => [
        'identical_code' => 'auction_canceled_200',
        'message' => 'Auction canceled successfully',
        'http_response_code' => 200,
    ],

    'wallet_inactive' => [
        'identical_code' => 'wallet_inactive_403',
        'message' => 'The wallet is not available right now',
        'http_response_code' => 403,
    ],

    'wallet_fund_inactive' => [
        'identical_code' => 'wallet_fund_inactive_403',
        'message' => 'Adding funds to the wallet is turned off right now',
        'http_response_code' => 403,
    ],

    'wallet_fund_amount_out_of_range' => [
        'identical_code' => 'wallet_fund_amount_out_of_range_422',
        'message' => 'Enter an amount within the allowed range',
        'http_response_code' => 422,
    ],

    'wallet_fund_payment_link' => [
        'identical_code' => 'wallet_fund_payment_link_200',
        'message' => 'Continue to the payment page to add funds',
        'http_response_code' => 200,
    ],

    'loyalty_point_inactive' => [
        'identical_code' => 'loyalty_point_inactive_403',
        'message' => 'Loyalty points are not available right now',
        'http_response_code' => 403,
    ],

    'loyalty_point_conversion_unavailable' => [
        'identical_code' => 'loyalty_point_conversion_unavailable_403',
        'message' => 'Converting loyalty points is not possible right now',
        'http_response_code' => 403,
    ],

    'loyalty_point_below_minimum' => [
        'identical_code' => 'loyalty_point_below_minimum_422',
        'message' => 'You have not reached the minimum points needed to convert',
        'http_response_code' => 422,
    ],

    'loyalty_point_insufficient' => [
        'identical_code' => 'loyalty_point_insufficient_422',
        'message' => 'You do not have that many loyalty points',
        'http_response_code' => 422,
    ],

    'loyalty_point_converted' => [
        'identical_code' => 'loyalty_point_converted_200',
        'message' => 'Points transferred to your wallet successfully',
        'http_response_code' => 200,
    ],

    'auction_not_found' => [
        'identical_code' => 'auction_not_found_404',
        'message' => 'This auction was not found',
        'http_response_code' => 404,
    ],

    'auction_already_canceled' => [
        'identical_code' => 'auction_already_canceled_422',
        'message' => 'This auction is already canceled',
        'http_response_code' => 422,
    ],

    'auction_stage_blocked' => [
        'identical_code' => 'auction_stage_blocked_422',
        'message' => 'This auction cannot be canceled at its current stage',
        'http_response_code' => 422,
    ],

    'auction_has_paid_participants' => [
        'identical_code' => 'auction_has_paid_participants_422',
        'message' => 'This auction has participants who already paid the entry fee',
        'http_response_code' => 422,
    ],

    'auction_claim_unpaid' => [
        'identical_code' => 'auction_claim_unpaid_403',
        'message' => 'Delivery status can only be updated once the claim payment is settled',
        'http_response_code' => 403,
    ],

    'auction_claim_already_paid' => [
        'identical_code' => 'auction_claim_already_paid_422',
        'message' => 'Payment status already paid',
        'http_response_code' => 422,
    ],

    'auction_delete_stage_blocked' => [
        'identical_code' => 'auction_delete_stage_blocked_422',
        'message' => 'Auction cannot be deleted at this stage',
        'http_response_code' => 422,
    ],

    'auction_not_purchase_complete' => [
        'identical_code' => 'auction_not_purchase_complete_403',
        'message' => 'Payment status can only be updated once the auction is purchase complete',
        'http_response_code' => 403,
    ],

    'auction_invalid_delivery_transition' => [
        'identical_code' => 'auction_invalid_delivery_transition_422',
        'message' => 'This delivery status cannot be set from the current one',
        'http_response_code' => 422,
    ],

    'auction_offline_payment_admin_only' => [
        'identical_code' => 'auction_offline_payment_admin_only_403',
        'message' => 'An offline claim payment can only be updated by the admin',
        'http_response_code' => 403,
    ],

    'auction_withdraw_submitted' => [
        'identical_code' => 'auction_withdraw_submitted_201',
        'message' => 'Withdraw request submitted successfully',
        'http_response_code' => 201,
    ],

    'auction_withdraw_updated' => [
        'identical_code' => 'auction_withdraw_updated_200',
        'message' => 'Withdraw request updated successfully',
        'http_response_code' => 200,
    ],

    'auction_withdraw_not_claimed' => [
        'identical_code' => 'auction_withdraw_not_claimed_422',
        'message' => 'A payout can only be requested once the buyer has claimed this auction',
        'http_response_code' => 422,
    ],

    'auction_withdraw_claim_unpaid' => [
        'identical_code' => 'auction_withdraw_claim_unpaid_422',
        'message' => 'The claim payment for this auction is not settled yet',
        'http_response_code' => 422,
    ],

    'auction_withdraw_already_requested' => [
        'identical_code' => 'auction_withdraw_already_requested_409',
        'message' => 'A withdraw request already exists for this auction',
        'http_response_code' => 409,
    ],

    'auction_withdraw_nothing_payable' => [
        'identical_code' => 'auction_withdraw_nothing_payable_422',
        'message' => 'There is nothing to withdraw for this auction',
        'http_response_code' => 422,
    ],

    'auction_withdraw_not_amendable' => [
        'identical_code' => 'auction_withdraw_not_amendable_404',
        'message' => 'That withdraw request cannot be amended',
        'http_response_code' => 404,
    ],

    'auction_feature_disabled' => [
        'identical_code' => 'auction_feature_disabled_403',
        'message' => 'Auction feature is not available for vendors',
        'http_response_code' => 403,
    ],

    'auction_feature_unavailable' => [
        'identical_code' => 'auction_feature_disabled_403',
        'message' => 'Auction is not available now',
        'http_response_code' => 403,
    ],

    'auction_withdraw_method_invalid' => [
        'identical_code' => 'auction_withdraw_method_invalid_422',
        'message' => 'Choose a valid withdraw method',
        'http_response_code' => 422,
    ],

    'auction_withdraw_bank_info_missing' => [
        'identical_code' => 'auction_withdraw_bank_info_missing_422',
        'message' => 'Add your bank information before requesting a payout',
        'http_response_code' => 422,
    ],

    'auction_withdraw_method_fields_missing' => [
        'identical_code' => 'auction_withdraw_method_fields_missing_422',
        'message' => 'Fill in every required field of the withdraw method you chose',
        'http_response_code' => 422,
    ],

    'auction_relaunched' => [
        'identical_code' => 'auction_relaunched_200',
        'message' => 'Auction relaunched successfully',
        'http_response_code' => 200,
    ],

    'auction_last_image_remaining' => [
        'identical_code' => 'auction_last_image_remaining_422',
        'message' => 'Cannot delete the last remaining image',
        'http_response_code' => 422,
    ],

    'auction_not_unsold' => [
        'identical_code' => 'auction_not_unsold_403',
        'message' => 'Only an unsold auction can be relaunched',
        'http_response_code' => 403,
    ],

    'auction_deleted' => [
        'identical_code' => 'auction_deleted_200',
        'message' => 'Auction deleted successfully',
        'http_response_code' => 200,
    ],

    'auction_live_delete_blocked' => [
        'identical_code' => 'auction_live_delete_blocked_422',
        'message' => 'Cannot delete a live auction',
        'http_response_code' => 422,
    ],

    'auction_added' => [
        'identical_code' => 'auction_added_200',
        'message' => 'Auction added successfully',
        'http_response_code' => 200,
    ],

    'auction_updated' => [
        'identical_code' => 'auction_updated_200',
        'message' => 'Auction updated successfully',
        'http_response_code' => 200,
    ],

    'auction_status_updated' => [
        'identical_code' => 'auction_status_updated_200',
        'message' => 'Status updated successfully',
        'http_response_code' => 200,
    ],

    'auction_status_locked_by_bids' => [
        'identical_code' => 'auction_status_locked_by_bids_403',
        'message' => 'Status cannot be changed once bids have been placed',
        'http_response_code' => 403,
    ],

    'auction_joined' => [
        'identical_code' => 'auction_joined_200',
        'message' => 'You have joined the auction successfully',
        'http_response_code' => 200,
    ],

    'auction_entry_fee_paid' => [
        'identical_code' => 'auction_entry_fee_paid_200',
        'message' => 'Entry fee paid successfully',
        'http_response_code' => 200,
    ],

    'auction_entry_fee_submitted' => [
        'identical_code' => 'auction_entry_fee_submitted_200',
        'message' => 'Entry fee payment submitted and waiting for approval',
        'http_response_code' => 200,
    ],

    'auction_entry_fee_payment_link' => [
        'identical_code' => 'auction_entry_fee_payment_link_200',
        'message' => 'Continue to the payment page to pay the entry fee',
        'http_response_code' => 200,
    ],

    'auction_entry_fee_stage_blocked' => [
        'identical_code' => 'auction_entry_fee_stage_blocked_403',
        'message' => 'You can only join a live or upcoming auction',
        'http_response_code' => 403,
    ],

    'auction_entry_fee_already_paid' => [
        'identical_code' => 'auction_entry_fee_already_paid_409',
        'message' => 'You have already paid the entry fee for this auction',
        'http_response_code' => 409,
    ],

    'auction_entry_fee_misconfigured' => [
        'identical_code' => 'auction_entry_fee_misconfigured_403',
        'message' => 'The entry fee is not set up correctly, please try again later',
        'http_response_code' => 403,
    ],

    'auction_entry_fee_required' => [
        'identical_code' => 'auction_entry_fee_required_403',
        'message' => 'Pay the entry fee before placing a bid',
        'http_response_code' => 403,
    ],

    'auction_insufficient_wallet_balance' => [
        'identical_code' => 'auction_insufficient_wallet_balance_422',
        'message' => 'Your wallet balance is not enough for this payment',
        'http_response_code' => 422,
    ],

    'auction_not_live' => [
        'identical_code' => 'auction_not_live_403',
        'message' => 'This auction is not live right now',
        'http_response_code' => 403,
    ],

    'auction_shop_closed' => [
        'identical_code' => 'auction_shop_closed_403',
        'message' => 'This shop is temporarily closed or on vacation and is not accepting bids right now',
        'http_response_code' => 403,
    ],

    'auction_participation_shop_closed' => [
        'identical_code' => 'auction_participation_shop_closed_403',
        'message' => 'This shop is temporarily closed or on vacation',
        'http_response_code' => 403,
    ],

    'auction_bid_placed' => [
        'identical_code' => 'auction_bid_placed_200',
        'message' => 'Bid placed successfully',
        'http_response_code' => 200,
    ],

    'auction_bid_rolled_back' => [
        'identical_code' => 'auction_bid_rolled_back_200',
        'message' => 'Bid lowered successfully',
        'http_response_code' => 200,
    ],

    'auction_bids_withdrawn' => [
        'identical_code' => 'auction_bids_withdrawn_200',
        'message' => 'Your bids were withdrawn successfully',
        'http_response_code' => 200,
    ],

    'auction_bid_below_minimum' => [
        'identical_code' => 'auction_bid_below_minimum_422',
        'message' => 'Your bid is below the minimum this auction accepts',
        'http_response_code' => 422,
    ],

    'auction_no_bid_to_rollback' => [
        'identical_code' => 'auction_no_bid_to_rollback_404',
        'message' => 'You have no bid to lower on this auction',
        'http_response_code' => 404,
    ],

    'auction_not_highest_bidder' => [
        'identical_code' => 'auction_not_highest_bidder_403',
        'message' => 'Only the current highest bidder can lower their bid',
        'http_response_code' => 403,
    ],

    'auction_rollback_already_used' => [
        'identical_code' => 'auction_rollback_already_used_403',
        'message' => 'You have already lowered your bid once on this auction',
        'http_response_code' => 403,
    ],

    'auction_rollback_not_lower' => [
        'identical_code' => 'auction_rollback_not_lower_422',
        'message' => 'The new amount must be lower than your current bid',
        'http_response_code' => 422,
    ],

    'auction_rollback_below_minimum' => [
        'identical_code' => 'auction_rollback_below_minimum_422',
        'message' => 'The new amount is below the minimum this auction accepts',
        'http_response_code' => 422,
    ],

    'auction_no_active_bids' => [
        'identical_code' => 'auction_no_active_bids_404',
        'message' => 'You have no active bids to withdraw on this auction',
        'http_response_code' => 404,
    ],

    'auction_saved' => [
        'identical_code' => 'auction_saved_200',
        'message' => 'Auction saved successfully',
        'http_response_code' => 200,
    ],

    'auction_unsaved' => [
        'identical_code' => 'auction_unsaved_200',
        'message' => 'Auction removed from your saved list',
        'http_response_code' => 200,
    ],

    'auction_claimed' => [
        'identical_code' => 'auction_claimed_200',
        'message' => 'Auction claimed and paid successfully',
        'http_response_code' => 200,
    ],

    'auction_claim_submitted' => [
        'identical_code' => 'auction_claim_submitted_200',
        'message' => 'Auction claim submitted and waiting for payment confirmation',
        'http_response_code' => 200,
    ],

    'auction_claim_payment_link' => [
        'identical_code' => 'auction_claim_payment_link_200',
        'message' => 'Continue to the payment page to complete your claim',
        'http_response_code' => 200,
    ],

    'auction_not_ended' => [
        'identical_code' => 'auction_not_ended_403',
        'message' => 'This auction has not ended yet',
        'http_response_code' => 403,
    ],

    'auction_no_bids' => [
        'identical_code' => 'auction_no_bids_403',
        'message' => 'No bids were placed on this auction',
        'http_response_code' => 403,
    ],

    'auction_claim_not_eligible' => [
        'identical_code' => 'auction_claim_not_eligible_403',
        'message' => 'You are not eligible to claim this auction',
        'http_response_code' => 403,
    ],

    'auction_no_eligible_bid' => [
        'identical_code' => 'auction_no_eligible_bid_403',
        'message' => 'You have no bid eligible to claim this auction',
        'http_response_code' => 403,
    ],

    'auction_already_claimed' => [
        'identical_code' => 'auction_already_claimed_409',
        'message' => 'You have already claimed this auction',
        'http_response_code' => 409,
    ],

    'auction_shipping_address_incomplete' => [
        'identical_code' => 'auction_shipping_address_incomplete_422',
        'message' => 'Fill in every shipping address field before claiming',
        'http_response_code' => 422,
    ],

    'auction_cod_unavailable' => [
        'identical_code' => 'auction_cod_unavailable_403',
        'message' => 'Cash on delivery is not available right now',
        'http_response_code' => 403,
    ],

    'auction_commission_phone_missing' => [
        'identical_code' => 'auction_commission_phone_missing_422',
        'message' => 'Add your phone number before paying the commission',
        'http_response_code' => 422,
    ],

    'auction_commission_paid' => [
        'identical_code' => 'auction_commission_paid_200',
        'message' => 'Commission paid successfully from your wallet',
        'http_response_code' => 200,
    ],

    'auction_commission_submitted' => [
        'identical_code' => 'auction_commission_submitted_200',
        'message' => 'Commission payment submitted for admin approval',
        'http_response_code' => 200,
    ],

    'auction_commission_payment_link' => [
        'identical_code' => 'auction_commission_payment_link_200',
        'message' => 'Payment link generated',
        'http_response_code' => 200,
    ],

    'auction_commission_already_paid' => [
        'identical_code' => 'auction_commission_already_paid_409',
        'message' => 'Commission has already been paid for this auction',
        'http_response_code' => 409,
    ],

    'auction_commission_nothing_due' => [
        'identical_code' => 'auction_commission_nothing_due_422',
        'message' => 'No commission is due for this auction',
        'http_response_code' => 422,
    ],

    'auction_commission_not_payable_stage' => [
        'identical_code' => 'auction_commission_not_payable_stage_403',
        'message' => 'Commission can only be paid once the buyer has claimed this auction',
        'http_response_code' => 403,
    ],

    'auction_commission_wallet_missing' => [
        'identical_code' => 'auction_commission_wallet_missing_422',
        'message' => 'Your wallet could not be found',
        'http_response_code' => 422,
    ],

    'auction_commission_insufficient_balance' => [
        'identical_code' => 'auction_commission_insufficient_balance_422',
        'message' => 'Your wallet balance is not enough to pay this commission',
        'http_response_code' => 422,
    ],

    'auction_offline_payment_method_invalid' => [
        'identical_code' => 'auction_offline_payment_method_invalid_422',
        'message' => 'Please select an active offline payment method',
        'http_response_code' => 422,
    ],

    'cart_item_added' => [
        'identical_code' => 'cart_item_added_200',
        'message' => 'Successfully added',
        'http_response_code' => 200,
    ],

    'cart_item_added_for_checkout' => [
        'identical_code' => 'cart_item_added_for_checkout_200',
        'message' => 'Added successfully',
        'http_response_code' => 200,
    ],

    'cart_shipping_method_required' => [
        'identical_code' => 'cart_shipping_method_required_200',
        'message' => 'Select shipping method',
        'http_response_code' => 200,
    ],

    'cart_product_not_found' => [
        'identical_code' => 'cart_product_not_found_404',
        'message' => 'Product not found',
        'http_response_code' => 404,
    ],

    'cart_product_unavailable' => [
        'identical_code' => 'cart_product_unavailable_422',
        'message' => 'Product is unavailable',
        'http_response_code' => 422,
    ],

    'cart_shop_unavailable' => [
        'identical_code' => 'cart_shop_unavailable_422',
        'message' => 'Shop is unavailable',
        'http_response_code' => 422,
    ],

    'cart_out_of_stock' => [
        'identical_code' => 'cart_out_of_stock_422',
        'message' => 'Out of Stock',
        'http_response_code' => 422,
    ],

    'cart_below_minimum_order_quantity' => [
        'identical_code' => 'cart_below_minimum_order_quantity_422',
        'message' => 'Minimum order quantity',
        'http_response_code' => 422,
    ],

    'cart_below_minimum_order_amount' => [
        'identical_code' => 'cart_below_minimum_order_amount_422',
        'message' => 'Minimum Order Amount',
        'http_response_code' => 422,
    ],

    'cart_shipping_method_not_found' => [
        'identical_code' => 'cart_shipping_method_not_found_404',
        'message' => 'Not found',
        'http_response_code' => 404,
    ],

    'cart_shipping_unavailable' => [
        'identical_code' => 'cart_shipping_unavailable_422',
        'message' => 'Shipping Not Available for this Shop',
        'http_response_code' => 422,
    ],

    'cart_item_not_found' => [
        'identical_code' => 'cart_item_not_found_404',
        'message' => 'Product not found in cart',
        'http_response_code' => 404,
    ],

    'cart_quantity_updated' => [
        'identical_code' => 'cart_quantity_updated_200',
        'message' => 'Updated successfully',
        'http_response_code' => 200,
    ],

    'cart_stock_limited' => [
        'identical_code' => 'cart_stock_limited_422',
        'message' => 'Sorry stock is limited',
        'http_response_code' => 422,
    ],

    'cart_item_removed' => [
        'identical_code' => 'cart_item_removed_200',
        'message' => 'Successfully removed',
        'http_response_code' => 200,
    ],

    'cart_cleared' => [
        'identical_code' => 'cart_cleared_200',
        'message' => 'Successfully removed',
        'http_response_code' => 200,
    ],

    'cart_selection_updated' => [
        'identical_code' => 'cart_selection_updated_200',
        'message' => 'Updated successfully',
        'http_response_code' => 200,
    ],

    'cart_guest_merged' => [
        'identical_code' => 'cart_guest_merged_200',
        'message' => 'Updated successfully',
        'http_response_code' => 200,
    ],

    'restock_request_sent' => [
        'identical_code' => 'restock_request_sent_200',
        'message' => 'Request sent successfully',
        'http_response_code' => 200,
    ],

    'restock_request_already_sent' => [
        'identical_code' => 'restock_request_already_sent_409',
        'message' => 'Already Requested',
        'http_response_code' => 409,
    ],

    'restock_request_product_not_found' => [
        'identical_code' => 'restock_request_product_not_found_404',
        'message' => 'Product not found',
        'http_response_code' => 404,
    ],

    'restock_request_sign_in_required' => [
        'identical_code' => 'restock_request_sign_in_required_403',
        'message' => 'Please sign in to request a restock',
        'http_response_code' => 403,
    ],

    'restock_request_removed' => [
        'identical_code' => 'restock_request_removed_200',
        'message' => 'Deleted successfully',
        'http_response_code' => 200,
    ],

    'restock_request_not_found' => [
        'identical_code' => 'restock_request_not_found_404',
        'message' => 'This item was not found',
        'http_response_code' => 404,
    ],

    'shipping_method_chosen' => [
        'identical_code' => 'shipping_method_chosen_200',
        'message' => 'Successfully added',
        'http_response_code' => 200,
    ],

    'shipping_cart_group_not_found' => [
        'identical_code' => 'shipping_cart_group_not_found_404',
        'message' => 'Cart group not found',
        'http_response_code' => 404,
    ],

    'shipping_method_unavailable' => [
        'identical_code' => 'shipping_method_unavailable_422',
        'message' => 'Shipping Not Available for this Shop',
        'http_response_code' => 422,
    ],

    'coupon_discount_calculated' => [
        'identical_code' => 'coupon_discount_calculated_200',
        'message' => 'Coupon applied successfully',
        'http_response_code' => 200,
    ],

    'coupon_code_invalid' => [
        'identical_code' => 'coupon_code_invalid_404',
        'message' => 'Invalid coupon',
        'http_response_code' => 404,
    ],

    'coupon_usage_limit_reached' => [
        'identical_code' => 'coupon_usage_limit_reached_422',
        'message' => 'Sorry this coupon is not valid for this user',
        'http_response_code' => 422,
    ],

    'coupon_not_first_order' => [
        'identical_code' => 'coupon_not_first_order_422',
        'message' => 'Sorry this coupon is not valid for this user',
        'http_response_code' => 422,
    ],

    'coupon_cart_empty' => [
        'identical_code' => 'coupon_cart_empty_422',
        'message' => 'Please add item to cart',
        'http_response_code' => 422,
    ],

    'coupon_customer_not_eligible' => [
        'identical_code' => 'coupon_customer_not_eligible_422',
        'message' => 'Coupon not valid',
        'http_response_code' => 422,
    ],

    'coupon_vendor_not_applicable' => [
        'identical_code' => 'coupon_vendor_not_applicable_422',
        'message' => 'Coupon not applicable to selected vendors',
        'http_response_code' => 422,
    ],

    'coupon_unavailable_products' => [
        'identical_code' => 'coupon_unavailable_products_422',
        'message' => 'Coupon not applicable to unavailable products',
        'http_response_code' => 422,
    ],

    'coupon_module_unavailable' => [
        'identical_code' => 'coupon_module_unavailable_422',
        'message' => 'Wholesale coupons are not available right now',
        'http_response_code' => 422,
    ],

    'coupon_minimum_purchase_not_reached' => [
        'identical_code' => 'coupon_minimum_purchase_not_reached_422',
        'message' => 'Minimum purchase amount not reached for this coupon.',
        'http_response_code' => 422,
    ],

    'coupon_shipping_cost_not_applicable' => [
        'identical_code' => 'coupon_shipping_cost_not_applicable_422',
        'message' => 'Not applicable for current shipping cost',
        'http_response_code' => 422,
    ],

    'coupon_no_discount' => [
        'identical_code' => 'coupon_no_discount_422',
        'message' => 'Coupon not valid',
        'http_response_code' => 422,
    ],

    'order_placed' => [
        'identical_code' => 'order_placed_200',
        'message' => 'Order placed successfully',
        'http_response_code' => 200,
    ],

    'order_cart_out_of_stock' => [
        'identical_code' => 'order_cart_out_of_stock_403',
        'message' => 'The following items in your cart are currently out of stock',
        'http_response_code' => 403,
    ],

    'order_minimum_amount_required' => [
        'identical_code' => 'order_minimum_amount_required_403',
        'message' => 'Check minimum order amount requirement',
        'http_response_code' => 403,
    ],

    'order_shipping_method_required' => [
        'identical_code' => 'order_shipping_method_required_403',
        'message' => 'Select shipping method',
        'http_response_code' => 403,
    ],

    'order_customer_already_registered' => [
        'identical_code' => 'order_customer_already_registered_403',
        'message' => 'Already registered',
        'http_response_code' => 403,
    ],

    'order_address_not_found' => [
        'identical_code' => 'order_address_not_found_403',
        'message' => 'Address not found',
        'http_response_code' => 403,
    ],

    'order_delivery_country_unavailable' => [
        'identical_code' => 'order_delivery_country_unavailable_403',
        'message' => 'Delivery unavailable for this country',
        'http_response_code' => 403,
    ],

    'order_delivery_zip_code_unavailable' => [
        'identical_code' => 'order_delivery_zip_code_unavailable_403',
        'message' => 'Delivery unavailable for this zip code area',
        'http_response_code' => 403,
    ],

    'order_wallet_insufficient_balance' => [
        'identical_code' => 'order_wallet_insufficient_balance_403',
        'message' => 'Insufficient balance in your wallet to pay for this order',
        'http_response_code' => 403,
    ],

    'order_wallet_disabled' => [
        'identical_code' => 'order_wallet_disabled_403',
        'message' => 'Wallet is deactivated',
        'http_response_code' => 403,
    ],

    'order_placement_failed' => [
        'identical_code' => 'order_placement_failed_500',
        'message' => 'Something went wrong',
        'http_response_code' => 500,
    ],

    'order_canceled' => [
        'identical_code' => 'order_canceled_200',
        'message' => 'Order canceled successfully',
        'http_response_code' => 200,
    ],

    'order_status_not_changeable' => [
        'identical_code' => 'order_status_not_changeable_403',
        'message' => 'Status not changeable now',
        'http_response_code' => 403,
    ],

    'order_reorder_added' => [
        'identical_code' => 'order_reorder_added_200',
        'message' => 'Added to cart successfully',
        'http_response_code' => 200,
    ],

    'order_reorder_updated' => [
        'identical_code' => 'order_reorder_updated_200',
        'message' => 'Updated successfully',
        'http_response_code' => 200,
    ],

    'order_reorder_added_with_unavailable' => [
        'identical_code' => 'order_reorder_added_with_unavailable_200',
        'message' => 'Added to cart successfully!',
        'http_response_code' => 200,
    ],

    'order_reorder_partially_added' => [
        'identical_code' => 'order_reorder_partially_added_200',
        'message' => 'Some items were not added to your cart because they are currently unavailable for purchase.!',
        'http_response_code' => 200,
    ],

    'order_reorder_unavailable' => [
        'identical_code' => 'order_reorder_unavailable_403',
        'message' => 'All items were not added to cart as they are currently unavailable for purchase',
        'http_response_code' => 403,
    ],

    'order_tracking_not_found' => [
        'identical_code' => 'order_tracking_not_found_404',
        'message' => 'Invalid Order Id or Phone Number',
        'http_response_code' => 404,
    ],

    'refund_order_item_not_found' => [
        'identical_code' => 'refund_order_item_not_found_404',
        'message' => 'Order item not found',
        'http_response_code' => 404,
    ],

    'refund_insufficient_loyalty_point' => [
        'identical_code' => 'refund_insufficient_loyalty_point_403',
        'message' => 'You do not have sufficient loyalty points to refund this order',
        'http_response_code' => 403,
    ],

    'refund_order_not_delivered' => [
        'identical_code' => 'refund_order_not_delivered_403',
        'message' => 'You can request for refund after order delivered',
        'http_response_code' => 403,
    ],

    'refund_window_closed' => [
        'identical_code' => 'refund_window_closed_403',
        'message' => 'The refund window for this item has closed',
        'http_response_code' => 403,
    ],

    'refund_not_found' => [
        'identical_code' => 'refund_not_found_404',
        'message' => 'No refund request found for this item',
        'http_response_code' => 404,
    ],

    'refund_already_requested' => [
        'identical_code' => 'refund_already_requested_409',
        'message' => 'Already applied for refund request',
        'http_response_code' => 409,
    ],

    'refund_requested' => [
        'identical_code' => 'refund_requested_200',
        'message' => 'Refund requested successfully',
        'http_response_code' => 200,
    ],

    'digital_download_payment_unconfirmed' => [
        'identical_code' => 'digital_download_payment_unconfirmed_403',
        'message' => 'Payment must be confirmed first',
        'http_response_code' => 403,
    ],

    'digital_download_file_not_found' => [
        'identical_code' => 'digital_download_file_not_found_404',
        'message' => 'File not found',
        'http_response_code' => 404,
    ],

    'digital_download_otp_unavailable' => [
        'identical_code' => 'digital_download_otp_unavailable_403',
        'message' => 'The OTP could not be sent because no email or SMS service is set up',
        'http_response_code' => 403,
    ],

    'digital_download_otp_contact_missing' => [
        'identical_code' => 'digital_download_otp_contact_missing_403',
        'message' => 'There is no email or phone number to send the OTP to',
        'http_response_code' => 403,
    ],

    'digital_download_otp_sent' => [
        'identical_code' => 'digital_download_otp_sent_200',
        'message' => 'OTP sent successfully',
        'http_response_code' => 200,
    ],

    'digital_download_otp_send_failed' => [
        'identical_code' => 'digital_download_otp_send_failed_403',
        'message' => 'OTP sending failed',
        'http_response_code' => 403,
    ],

    'digital_download_otp_incorrect' => [
        'identical_code' => 'digital_download_otp_incorrect_403',
        'message' => 'The OTP is incorrect',
        'http_response_code' => 403,
    ],

    'order_due_payment_successful' => [
        'identical_code' => 'order_due_payment_successful_200',
        'message' => 'Payment successful',
        'http_response_code' => 200,
    ],

    'order_due_payment_method_updated' => [
        'identical_code' => 'order_due_payment_method_updated_200',
        'message' => 'Payment method updated',
        'http_response_code' => 200,
    ],

    'order_due_payment_link_created' => [
        'identical_code' => 'order_due_payment_link_created_200',
        'message' => 'Payment link created successfully',
        'http_response_code' => 200,
    ],

    'order_due_wallet_insufficient_balance' => [
        'identical_code' => 'order_due_wallet_insufficient_balance_403',
        'message' => 'Insufficient Wallet Balance',
        'http_response_code' => 403,
    ],

    'order_due_wallet_customer_required' => [
        'identical_code' => 'order_due_wallet_customer_required_401',
        'message' => 'Please sign in to pay from your wallet',
        'http_response_code' => 401,
    ],

    'order_due_payment_link_failed' => [
        'identical_code' => 'order_due_payment_link_failed_403',
        'message' => 'Payment link could not be created',
        'http_response_code' => 403,
    ],

    'order_due_payment_failed' => [
        'identical_code' => 'order_due_payment_failed_500',
        'message' => 'Something went wrong',
        'http_response_code' => 500,
    ],

    'digital_payment_link_created' => [
        'identical_code' => 'digital_payment_link_created_200',
        'message' => 'Payment link created successfully',
        'http_response_code' => 200,
    ],

    'digital_payment_checkout_blocked' => [
        'identical_code' => 'digital_payment_checkout_blocked_403',
        'message' => 'Please review your cart before checkout',
        'http_response_code' => 403,
    ],

    'digital_payment_shipping_method_required' => [
        'identical_code' => 'digital_payment_shipping_method_required_403',
        'message' => 'Select shipping method',
        'http_response_code' => 403,
    ],

    'digital_payment_link_failed' => [
        'identical_code' => 'digital_payment_link_failed_403',
        'message' => 'Payment link could not be created',
        'http_response_code' => 403,
    ],

    'payment_default_currency_unavailable' => [
        'identical_code' => 'payment_default_currency_unavailable_403',
        'message' => 'Something went wrong',
        'http_response_code' => 403,
    ],

    'wishlist_product_added' => [
        'identical_code' => 'wishlist_product_added_200',
        'message' => 'Successfully added',
        'http_response_code' => 200,
    ],

    'wishlist_product_already_added' => [
        'identical_code' => 'wishlist_product_already_added_409',
        'message' => 'Already in your wishlist',
        'http_response_code' => 409,
    ],

    'wishlist_product_not_found' => [
        'identical_code' => 'wishlist_product_not_found_404',
        'message' => 'Product not found',
        'http_response_code' => 404,
    ],

    'wishlist_product_removed' => [
        'identical_code' => 'wishlist_product_removed_200',
        'message' => 'Successfully removed',
        'http_response_code' => 200,
    ],

    'wishlist_item_not_found' => [
        'identical_code' => 'wishlist_item_not_found_404',
        'message' => 'No such data found',
        'http_response_code' => 404,
    ],

    'compare_product_added' => [
        'identical_code' => 'compare_product_added_200',
        'message' => 'Successfully added',
        'http_response_code' => 200,
    ],

    'compare_product_removed' => [
        'identical_code' => 'compare_product_removed_200',
        'message' => 'Product removed from the compare list',
        'http_response_code' => 200,
    ],

    'compare_product_replaced' => [
        'identical_code' => 'compare_product_replaced_200',
        'message' => 'Successfully added',
        'http_response_code' => 200,
    ],

    'compare_product_not_found' => [
        'identical_code' => 'compare_product_not_found_404',
        'message' => 'Product not found',
        'http_response_code' => 404,
    ],

    'compare_item_not_found' => [
        'identical_code' => 'compare_item_not_found_404',
        'message' => 'No such data found',
        'http_response_code' => 404,
    ],

    'compare_list_cleared' => [
        'identical_code' => 'compare_list_cleared_200',
        'message' => 'Compare list removed',
        'http_response_code' => 200,
    ],

    'product_review_submitted' => [
        'identical_code' => 'product_review_submitted_200',
        'message' => 'Successfully review submitted',
        'http_response_code' => 200,
    ],

    'product_review_updated' => [
        'identical_code' => 'product_review_updated_200',
        'message' => 'Successfully review updated',
        'http_response_code' => 200,
    ],

    'product_review_image_removed' => [
        'identical_code' => 'product_review_image_removed_200',
        'message' => 'Review image removed successfully',
        'http_response_code' => 200,
    ],

    'deliveryman_review_submitted' => [
        'identical_code' => 'deliveryman_review_submitted_200',
        'message' => 'Review added successfully',
        'http_response_code' => 200,
    ],

    'deliveryman_review_added' => [
        'identical_code' => 'deliveryman_review_added_200',
        'message' => 'Successfully added review',
        'http_response_code' => 200,
    ],

    'deliveryman_review_updated' => [
        'identical_code' => 'deliveryman_review_updated_200',
        'message' => 'Successfully update review',
        'http_response_code' => 200,
    ],

    'deliveryman_review_unavailable' => [
        'identical_code' => 'deliveryman_review_unavailable_403',
        'message' => 'Invalid review',
        'http_response_code' => 403,
    ],

    'category_not_found' => [
        'identical_code' => 'category_not_found_404',
        'message' => 'Category not found',
        'http_response_code' => 404,
    ],

    'brand_not_found' => [
        'identical_code' => 'brand_not_found_404',
        'message' => 'Brand not found',
        'http_response_code' => 404,
    ],

    'erp_connection_successful' => [
        'identical_code' => 'erp_connection_successful_200',
        'message' => 'Connection successful',
        'http_response_code' => 200,
    ],

    'seller_setup_guide_updated' => [
        'identical_code' => 'seller_setup_guide_updated_200',
        'message' => 'Setup guide updated successfully',
        'http_response_code' => 200,
    ],

    'seller_review_not_found' => [
        'identical_code' => 'seller_review_not_found_404',
        'message' => 'Review not found',
        'http_response_code' => 404,
    ],

    'seller_review_product_not_found' => [
        'identical_code' => 'seller_review_product_not_found_404',
        'message' => 'Product not found',
        'http_response_code' => 404,
    ],

    'seller_review_replied' => [
        'identical_code' => 'seller_review_replied_200',
        'message' => 'Review reply successfully',
        'http_response_code' => 200,
    ],

    'seller_review_status_updated' => [
        'identical_code' => 'seller_review_status_updated_200',
        'message' => 'Status updated successfully!!',
        'http_response_code' => 200,
    ],

    'order_delivery_man_assigned' => [
        'identical_code' => 'order_delivery_man_assigned_200',
        'message' => 'Order deliveryman assigned successfully',
        'http_response_code' => 200,
    ],

    'order_delivery_man_assign_not_allowed' => [
        'identical_code' => 'order_delivery_man_assign_not_allowed_403',
        'message' => 'A delivery man cannot be assigned to a delivered order',
        'http_response_code' => 403,
    ],

    'order_delivery_schedule_updated' => [
        'identical_code' => 'order_delivery_schedule_updated_200',
        'message' => 'Updated successfully!',
        'http_response_code' => 200,
    ],

    'order_delivery_schedule_update_failed' => [
        'identical_code' => 'order_delivery_schedule_update_failed_403',
        'message' => 'Update fail!',
        'http_response_code' => 403,
    ],

    'order_payment_status_updated' => [
        'identical_code' => 'order_payment_status_updated_200',
        'message' => 'Payment status updated',
        'http_response_code' => 200,
    ],

    'order_payment_status_cannot_revert' => [
        'identical_code' => 'order_payment_status_cannot_revert_403',
        'message' => 'A paid payment status cannot be changed back to unpaid.',
        'http_response_code' => 403,
    ],

    'order_payment_status_customer_deleted' => [
        'identical_code' => 'order_payment_status_customer_deleted_403',
        'message' => "Customer account has been deleted. you can't update status!",
        'http_response_code' => 403,
    ],

    'order_payment_status_before_delivery' => [
        'identical_code' => 'order_payment_status_before_delivery_403',
        'message' => 'Can not change payment status before order delivered!',
        'http_response_code' => 403,
    ],

    'order_status_updated' => [
        'identical_code' => 'order_status_updated_200',
        'message' => 'Order status updated successfully',
        'http_response_code' => 200,
    ],

    'order_status_customer_deleted' => [
        'identical_code' => 'order_status_customer_deleted_403',
        'message' => 'Customer account has been deleted You cant update status',
        'http_response_code' => 403,
    ],

    'order_status_offline_payment_unconfirmed' => [
        'identical_code' => 'order_status_offline_payment_unconfirmed_403',
        'message' => 'Please confirm the offline payment information before changing the order status.',
        'http_response_code' => 403,
    ],

    'order_status_edit_due_payment_unconfirmed' => [
        'identical_code' => 'order_status_edit_due_payment_unconfirmed_403',
        'message' => 'After admin confirm the due amount payment as paid then you can change this status.',
        'http_response_code' => 403,
    ],

    'order_status_edit_return_payment_unconfirmed' => [
        'identical_code' => 'order_status_edit_return_payment_unconfirmed_403',
        'message' => 'After admin confirm the return amount payment as returned then you can change this status.',
        'http_response_code' => 403,
    ],

    'order_status_edit_due_unpaid_before_delivery' => [
        'identical_code' => 'order_status_edit_due_unpaid_before_delivery_403',
        'message' => 'Please mark as paid before delivered this order.',
        'http_response_code' => 403,
    ],

    'order_status_already_delivered' => [
        'identical_code' => 'order_status_already_delivered_403',
        'message' => 'Order is already delivered',
        'http_response_code' => 403,
    ],

    'order_status_digital_file_pending' => [
        'identical_code' => 'order_status_digital_file_pending_403',
        'message' => 'Please upload the digital product files first',
        'http_response_code' => 403,
    ],

    'order_status_insufficient_stock' => [
        'identical_code' => 'order_status_insufficient_stock_403',
        'message' => 'There is not enough quantity in stock',
        'http_response_code' => 403,
    ],

    'order_item_not_found' => [
        'identical_code' => 'order_item_not_found_404',
        'message' => 'Order item not found',
        'http_response_code' => 404,
    ],

    'order_digital_file_uploaded' => [
        'identical_code' => 'order_digital_file_uploaded_200',
        'message' => 'File upload successfully',
        'http_response_code' => 200,
    ],

    'order_digital_file_not_applicable' => [
        'identical_code' => 'order_digital_file_not_applicable_403',
        'message' => 'Only a digital product delivered after sale takes a file upload',
        'http_response_code' => 403,
    ],

    'order_digital_file_upload_failed' => [
        'identical_code' => 'order_digital_file_upload_failed_403',
        'message' => 'File upload fail!',
        'http_response_code' => 403,
    ],

    'order_third_party_delivery_unavailable' => [
        'identical_code' => 'order_third_party_delivery_unavailable_403',
        'message' => 'Manual third party delivery assignment is no longer available Send the order to a delivery partner instead',
        'http_response_code' => 403,
    ],

    'order_address_updated' => [
        'identical_code' => 'order_address_updated_200',
        'message' => 'Address updated successfully',
        'http_response_code' => 200,
    ],

    'order_items_updated' => [
        'identical_code' => 'order_items_updated_200',
        'message' => 'Order updated successfully',
        'http_response_code' => 200,
    ],

    'order_items_payment_status_cannot_revert' => [
        'identical_code' => 'order_items_payment_status_cannot_revert_403',
        'message' => 'When payment status paid then you can not change payment status paid to unpaid.',
        'http_response_code' => 403,
    ],

    'order_edit_calculated' => [
        'identical_code' => 'order_edit_calculated_200',
        'message' => 'Order amount calculation successful',
        'http_response_code' => 200,
    ],

    'order_edit_updated' => [
        'identical_code' => 'order_edit_updated_200',
        'message' => 'Order edited successfully',
        'http_response_code' => 200,
    ],

    'order_edit_not_allowed' => [
        'identical_code' => 'order_edit_not_allowed_403',
        'message' => 'This order cannot be edited',
        'http_response_code' => 403,
    ],

    'order_edit_out_of_stock' => [
        'identical_code' => 'order_edit_out_of_stock_403',
        'message' => 'Out of Stock',
        'http_response_code' => 403,
    ],

    'order_edit_minimum_order_amount_not_met' => [
        'identical_code' => 'order_edit_minimum_order_amount_not_met_403',
        'message' => 'Please complete minimum Order Amount',
        'http_response_code' => 403,
    ],

    'order_edit_loyalty_points_redeemed' => [
        'identical_code' => 'order_edit_loyalty_points_redeemed_403',
        'message' => 'This order cannot be modified because its loyalty points have already been redeemed',
        'http_response_code' => 403,
    ],

    'order_edit_due_switched_to_cash_on_delivery' => [
        'identical_code' => 'order_edit_due_switched_to_cash_on_delivery_200',
        'message' => 'Switched to COD successfully',
        'http_response_code' => 200,
    ],

    'seller_refund_not_found' => [
        'identical_code' => 'seller_refund_not_found_404',
        'message' => 'Refund request not found',
        'http_response_code' => 404,
    ],

    'seller_refund_status_updated' => [
        'identical_code' => 'seller_refund_status_updated_200',
        'message' => 'Refund status updated',
        'http_response_code' => 200,
    ],

    'seller_refund_status_loyalty_point_insufficient' => [
        'identical_code' => 'seller_refund_status_loyalty_point_insufficient_403',
        'message' => 'Customer does not have sufficient loyalty points to take a refund for this order',
        'http_response_code' => 403,
    ],

    'seller_refund_status_customer_deleted' => [
        'identical_code' => 'seller_refund_status_customer_deleted_403',
        'message' => "Customer account has been deleted. you can't update status!",
        'http_response_code' => 403,
    ],

    'seller_refund_status_changed_by_admin' => [
        'identical_code' => 'seller_refund_status_changed_by_admin_403',
        'message' => 'Admin already changed the status',
        'http_response_code' => 403,
    ],

    'seller_refund_status_already_refunded' => [
        'identical_code' => 'seller_refund_status_already_refunded_403',
        'message' => 'Refunded status cannot be changed',
        'http_response_code' => 403,
    ],

    'seller_refund_status_change_limit_reached' => [
        'identical_code' => 'seller_refund_status_change_limit_reached_403',
        'message' => 'You have already changed this status the maximum number of times',
        'http_response_code' => 403,
    ],

    'pos_order_placed' => [
        'identical_code' => 'pos_order_placed_200',
        'message' => 'Order placed successfully',
        'http_response_code' => 200,
    ],

    'pos_order_digital_product_requires_customer' => [
        'identical_code' => 'pos_order_digital_product_requires_customer_403',
        'message' => 'To order digital product, kindly fill up the add new customer form',
        'http_response_code' => 403,
    ],

    'pos_order_wallet_insufficient_balance' => [
        'identical_code' => 'pos_order_wallet_insufficient_balance_403',
        'message' => 'Need sufficient amount balance',
        'http_response_code' => 403,
    ],

    'pos_order_product_unavailable' => [
        'identical_code' => 'pos_order_product_unavailable_403',
        'message' => 'Not available for order place',
        'http_response_code' => 403,
    ],

    'pos_invoice_access_denied' => [
        'identical_code' => 'pos_invoice_access_denied_403',
        'message' => 'Access denied!',
        'http_response_code' => 403,
    ],

    'product_status_update_not_allowed' => [
        'identical_code' => 'product_status_update_not_allowed_403',
        'message' => 'Product must be approved before it can be activated',
        'http_response_code' => 403,
    ],

    'product_quantity_updated' => [
        'identical_code' => 'product_quantity_updated_200',
        'message' => 'Successfully product updated',
        'http_response_code' => 200,
    ],

    'product_barcode_generated' => [
        'identical_code' => 'product_barcode_generated_200',
        'message' => 'Barcode generated successfully',
        'http_response_code' => 200,
    ],

    'product_barcode_code_missing' => [
        'identical_code' => 'product_barcode_code_missing_422',
        'message' => 'Please update product code!',
        'http_response_code' => 422,
    ],

    'product_image_uploaded' => [
        'identical_code' => 'product_image_uploaded_200',
        'message' => 'Image uploaded successfully',
        'http_response_code' => 200,
    ],

    'product_digital_file_uploaded' => [
        'identical_code' => 'product_digital_file_uploaded_200',
        'message' => 'File uploaded successfully',
        'http_response_code' => 200,
    ],

    'product_digital_file_deleted' => [
        'identical_code' => 'product_digital_file_deleted_200',
        'message' => 'Delete successful',
        'http_response_code' => 200,
    ],

    'product_preview_file_deleted' => [
        'identical_code' => 'product_preview_file_deleted_200',
        'message' => 'Preview file deleted',
        'http_response_code' => 200,
    ],

    'seller_restock_request_stock_updated' => [
        'identical_code' => 'seller_restock_request_stock_updated_200',
        'message' => 'Successfully product updated',
        'http_response_code' => 200,
    ],

    'seller_restock_request_removed' => [
        'identical_code' => 'seller_restock_request_removed_200',
        'message' => 'Product restock removed successfully',
        'http_response_code' => 200,
    ],

    'seller_restock_request_not_found' => [
        'identical_code' => 'seller_restock_request_not_found_404',
        'message' => 'Restock request not found',
        'http_response_code' => 404,
    ],

    'order_expected_delivery_updated' => [
        'identical_code' => 'order_expected_delivery_updated_200',
        'message' => 'Expected delivery date updated successfully',
        'http_response_code' => 200,
    ],

    'order_pause_status_updated' => [
        'identical_code' => 'order_pause_status_updated_200',
        'message' => 'Order pause status updated successfully',
        'http_response_code' => 200,
    ],

    'delivery_verification_code_sent' => [
        'identical_code' => 'delivery_verification_code_sent_200',
        'message' => 'Verification code sent to the customer',
        'http_response_code' => 200,
    ],

    'delivery_verification_code_send_failed' => [
        'identical_code' => 'delivery_verification_code_send_failed_403',
        'message' => 'Verification code could not be sent to the customer',
        'http_response_code' => 403,
    ],

    'delivery_verification_code_verified' => [
        'identical_code' => 'delivery_verification_code_verified_200',
        'message' => 'Delivery verification code verified successfully',
        'http_response_code' => 200,
    ],

    'delivery_verification_code_invalid' => [
        'identical_code' => 'delivery_verification_code_invalid_403',
        'message' => 'The verification code is not correct',
        'http_response_code' => 403,
    ],

    'delivery_proofs_uploaded' => [
        'identical_code' => 'delivery_proofs_uploaded_200',
        'message' => 'Delivery proof uploaded successfully',
        'http_response_code' => 200,
    ],

    'product_review_order_not_delivered' => [
        'identical_code' => 'product_review_order_not_delivered_403',
        'message' => 'You can review a product after the order has been delivered',
        'http_response_code' => 403,
    ],

];
