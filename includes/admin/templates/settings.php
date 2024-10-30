<div class="wrap swpm-admin-menu-wrap">
    <h1><?php echo esc_html__( 'Bot - Telegram Settings', 'wpbot-telegram' ); ?></h1>

    <h2 class="nav-tab-wrapper sld_nav_container wppt_nav_container">
        <a class="nav-tab sld_click_handle nav-tab-active" href="#general_settings"><?php echo esc_html__( 'General Settings', 'wpbot-telegram' ); ?></a>
        <!--<a class="nav-tab sld_click_handle" href="#url_based_default_language">URL Based Default Language</a>-->
    </h2>

    <form method="post" action="options.php">
        <?php settings_fields( 'qc-wpbottelegram-settings-group' ); ?>
        <?php do_settings_sections( 'qc-wpbottelegram-settings-group' ); ?>
        <div class="wppt-settings-section" id="general_settings">
            <table class="form-table">
                <tr valign="top">
                    <th scope="row"><?php echo esc_html__( 'Telegram Access Token', 'wpbot-telegram' ); ?></th>
                    <td>
                        <input style="width:400px" type="text" name="tg_access_token" id="tg_access_token" value="<?php echo get_option( 'tg_access_token' ); ?>" />
                        <br>
                        <i>
                        <?php 
                            printf(
                                esc_html__( '%1$s %2$s.', 'text-domain' ),
                                esc_html__( 'Please follow this article to create a', 'text-domain' ),
                                sprintf(
                                    '<a href="%s" target="_blank">%s</a>',
                                    esc_url( 'https://dev.quantumcloud.com/wpbot-pro/how-to-get-telegram-bot-api-token/' ),
                                    esc_html__( 'telegram access token', 'text-domain' )
                                )
                            );
                        ?>
                        </i>
                        
                    </td>
                </tr>

                <tr valign="top">
                    <th scope="row"><?php echo esc_html__( 'Webhook URL', 'wpbot-telegram' ); ?></th>
                    <td>
                        <input style="width:400px" type="text" disabled name="tg_webhook_url" id="tg_webhook_url" value="<?php echo home_url().'/wp-json/wpbot/v2/telegram'; ?>" />
                    </td>
                </tr>

                <tr valign="top">
                    <th scope="row"><?php echo esc_html__( 'Webhook Status', 'wpbot-telegram' ); ?></th>
                    <td>
                        <?php echo esc_html( $this->tg_status() ); ?>
                    </td>
                </tr>

            </table>
        </div>

        <?php submit_button(); ?>
    </form>

</div>