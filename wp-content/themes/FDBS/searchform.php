 <form method="get" id="searchform" action="<?php echo esc_url(home_url()); ?>/">
            <div class="input-group input-group-lg">
            <input type="text" class="form-control" name="s" id="s" value="" placeholder="<?php echo esc_attr_e('Enter VIN #, model, make...','framework'); ?>" />
            <span class="input-group-btn">
            <button type ="submit" name ="submit" class="btn btn-primary">Submit <i class="fa fa-search fa-lg"></i></button>
            </span> </div>
 </form>
