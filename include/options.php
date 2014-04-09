<div class="wrap"><h2>Editorial Control Status</h2>
     <form name="site" action="" method="post" id="notifier">
         <div id="review">
             <fieldset id="pendingdiv">
                 <legend><b><?php _e('Editorial Control settings are set to:') ?></b></legend>
             </fieldset>
             <br />
             <fieldset id="reviewdiv">
                 <div>
                     <input type="text" size="50" name="notificationemails" tabindex="1" id="notificationemails" value="<?php echo attribute_escape(get_option('notificationemails')); ?>"><br />
                     Enter the email address of who should be notified when a post is in a pending review status(comma separated).
                 </div>
                 <br/>
                 <div>
                     <label for="supercontributor" class="selectit">
                         <input type="checkbox" tabindex="2" id="supercontributor" name="supercontributor" value="yes" <?php if(get_option('supercontributor')=='yes') echo 'checked="checked"'; ?> />
                         Allow the contributor role to upload images.
                     </label>
                     <br/>
                     <label for="approvednotification" class="selectit">
                         <input type="checkbox" tabindex="2" id="approvednotification" name="approvednotification" value="yes" <?php if(get_option('approvednotification')=='yes') echo 'checked="checked"'; ?> />
                         Notify the contributor when their post is approved.
                     </label>
                     <br />
                     <label for="declinednotification" class="selectit">
                         <input type="checkbox" tabindex="3" id="declinednotification" name="declinednotification" value="yes" <?php if(get_option('declinednotification')=='yes') echo 'checked="checked"'; ?> />
                         Notify contributor when their post is declined (set back to draft).
                     </label>
                 </div>
             </fieldset>
             <br />
             <p class="submit">
                 <input name="save" type="submit" id="savenotifier" tabindex="6" style="font-weight: bold;" value="Save Settings" />
             </p>
         </div>
     </form>
     <small>Powered by WP Editorial Control</small>
</div>