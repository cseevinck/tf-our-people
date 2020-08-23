// Constant defintions 
// should match constants in constants.php 

// For tf_op_settings[display_all_field]
const INCLUDE_ALL = 2;

// For tf_op_settings[display_choice_field]
const DISPLAY_ELDERS = 1;
const DISPLAY_STAFF = 2;
const DISPLAY_ELDERS_STAFF = 3;
const NO_FORCED_DISPLAY = 4;

// End Constant defintions 

jQuery(document).ready(function () {
    jQuery(".user_like").click(function (e) {
        e.preventDefault();

        let post_id = jQuery(this).attr("data-post_id");
        let nonce = jQuery(this).attr("data-nonce");

        jQuery.ajax({
            type: "post",
            dataType: "json",
            url: myAjax.ajaxurl,
            data: { action: "do_tf_our_people", post_id: post_id, nonce: nonce, },
            success: function (response) {
            // console.log("post_id".post_id);
                fillAllPersonsHTML(response);
            },
            error: function (jqXHR, exception) {
                var msg = '';
                if (jqXHR.status === 0) {
                    msg = 'Not connect.\n Verify Network.';
                } else if (jqXHR.status == 404) {
                    msg = 'Requested page not found. [404]';
                } else if (jqXHR.status == 500) {
                    msg = 'Internal Server Error [500].';
                } else if (exception === 'parsererror') {
                    msg = 'Requested JSON parse failed.';
                } else if (exception === 'timeout') {
                    msg = 'Time out error.';
                } else if (exception === 'abort') {
                    msg = 'Ajax request aborted.';
                } else {
                    msg = 'Uncaught Error.\n' + jqXHR.responseText;
                }
                // document.write(msg);
                jQuery('#people-list').html(msg);
                jQuery('#people-list').attr({ "style": "color:red;text-align:center;" });
                // if error go to gary's php page
                window.location.replace("https://tf-sandy.org/our-people");
                fail(); // fail is not a thing - javascript will crash
            },
        });
    });

    /**
     * Create all my people (persons) entries HTML and add them to the webpage.
     */
    fillAllPersonsHTML = function (jsonData) {

        // delete the "loading spinner" if its there
        const spinner = document.getElementById('tf_spinner'); // parent for all
        if (spinner) {
            spinner.remove();
        }

        const div = document.getElementById('our-people-top'); // put top button here 
        // If not there - error
        if (!div){
            jQuery('#people-list').html('our-people-top not defined in options');
            jQuery('#people-list').attr({ "style": "color:red;text-align:center;" });
            fail(); // fail is not a thing - javascript will crash
        }

        // create the top "see more" button
        const button = document.createElement("button");
        button.className = 'a_button';
        button.className = 'et_pb_button et_pb_button_0 et_pb_bg_layout_light';
        button.setAttribute("id", "a_people_button_top");
        button.innerHTML = "See more people";
        div.appendChild(button);

        // Add event handler
        button.addEventListener("click", function () {
            // Build list of the people to display - shuffled
            buildList(jsonData);
        });

        // Build a list of the people to display - shuffled
        buildList(jsonData);
    };

    /**
     * Build sorted list of the people
     */

    buildList = function (jsonData) {
        let finalList = {};
        let numToKeep;

        const displayNumber = Number(jsonData.displayNumber);
        const displayAll = Number(jsonData.displayAll);
        const displayChoice = Number(jsonData.displayChoice);
        const phpSlug = String(jsonData.phpSlug);
        console.log("phpSlug = ");
        console.log(phpSlug);

        // shuffle all lists
        let leaders = jsonData.leaders.sort(() => 0.5 - Math.random());
        let persons = jsonData.persons.sort(() => 0.5 - Math.random());
        let elders = jsonData.elders.sort(() => 0.5 - Math.random());
        let staffs = jsonData.staffs.sort(() => 0.5 - Math.random());

        if (displayAll != INCLUDE_ALL) {
            switch (displayChoice) {
                case DISPLAY_ELDERS:
                    // keep displayNumber - number of elders (or 1), then combine the two lists
                    numToKeep = Number(displayNumber - elders.length);
                    if (numToKeep <= 0) {
                        numToKeep = 1; // If settings displayNumber <= number of leaders, force it to 1 
                    }
                    persons = persons.slice(0, numToKeep);
                    // Combine leaders and persons into one array
                    finalList = persons.concat(elders);
                    break;
                case DISPLAY_STAFF:
                    // keep displayNumber - number of staff members (or 1), then combine the two lists
                    numToKeep = Number(displayNumber - staffs.length);
                    if (numToKeep <= 0) {
                        numToKeep = 1; // If settings displayNumber <= number of leaders, force it to 1 
                    }
                    persons = persons.slice(0, numToKeep);
                    // Combine leaders and persons into one array
                    finalList = persons.concat(staffs);
                    break;
                case DISPLAY_ELDERS_STAFF:
                    // keep displayNumber - number of leaders (or 1), then combine the two lists
                    numToKeep = Number(displayNumber - leaders.length);
                    if (numToKeep <= 0) {
                        numToKeep = 1; // If settings displayNumber <= number of leaders, force it to 1 
                    }
                    persons = persons.slice(0, numToKeep);
                    // Combine leaders and persons into one array
                    finalList = persons.concat(leaders);
                    break;
                case NO_FORCED_DISPLAY:
                    // Combine leaders and persons into one array
                    persons = persons.concat(leaders);
                    persons = persons.sort(() => 0.5 - Math.random()); // shuffle before slice
                    finalList = persons.slice(0, displayNumber);
                    break;
                default:
                    console.log("Invalid tf_op_settings[display_choice_field] value = ".displayChoice);
            }
        } else { // here for display all
            finalList = persons.concat(leaders); // combine lists
        }

        // shuffle array
        finalList = finalList.sort(() => 0.5 - Math.random());

        // Delete all child dom entries for the old our people list
        jQuery('#a_div_envelope').remove();

        // build div that contains the people items
        const divP = document.getElementById('a_people_button_top');
        const div = document.createElement('div'); // div envelope
        div.setAttribute("id", "a_div_envelope");
        divP.parentNode.appendChild(div);

        // create all the items in a fragment first and then add it to dom
        var frag = document.createDocumentFragment();
        // loop to make people items
        finalList.forEach(person => {
            frag.append(createPersonHTML(person));
        });
        // now add the fragment into the dom
        div.appendChild(frag);

        insertBottomButton(jsonData);
    };

    /**
     * Create the bottom bottom "see more" button if its not already there
     */
    insertBottomButton = function (jsonData) {

        if (!document.getElementById('a_people_bottom_button')) {
            const div = document.getElementById('our-people-bottom');

            // If not there - error
            if (!div) {
                jQuery('#people-list').html('our-people-bottom not defined in options');
                jQuery('#people-list').attr({ "style": "color:red;text-align:center;" });
                fail(); // fail is not a thing - javascript will crash
            }
            // put in a <br> element to force new line
            const br = document.createElement("br");
            br.setAttribute("style", "clear: both");
            div.appendChild(br);

            const button = document.createElement("button");
            button.className = 'a_button';
            button.className = 'et_pb_button et_pb_button_0 et_pb_bg_layout_light';
            button.setAttribute("id", "a_people_bottom_button");
            button.setAttribute("style", "margin-top: 15px;");
            button.innerHTML = "See more people";
            div.appendChild(button);

            // Add event handler to the button
            button.addEventListener("click", function () {
                // Build a list of the people to display - shuffled
                buildList(jsonData);
            });
        }
    };

    /**
     * Create detailed my people (person) HTML.
     */
    createPersonHTML = function (person) {

        const div = document.createElement('div');
        div.className = 'a_people';

        const image = document.createElement('img');
        image.className = 'a_people_photo';
        image.src = person["photo"];
        div.append(image);
        image.setAttribute('alt', 'Photo of ' + person["name"]);

        const name = document.createElement('span');
        name.className = 'a_people_name';
        name.innerHTML = person["name"];
        div.append(name);

        let leader_staff_string = "";
        if (person.staff) {
            leader_staff_string = " Staff";
        } else if (person.elder) {
            leader_staff_string = " Elder";
        }
        if (person.elder && person.staff) {
            leader_staff_string = " Staff & Elder";
        }

        if (leader_staff_string != "") {
            const leader_staff = document.createElement('span');
            leader_staff.className = 'a_people_is_staff';
            leader_staff.innerHTML = leader_staff_string;
            div.append(leader_staff);
        }

        const bio = document.createElement('p');
        bio.className = 'a_people_bio';
        bio.innerHTML = person["bio"];
        div.append(bio);

        return div;
    };

    /**
     * Shuffle provided array - this code is not used - if the other shuffle proves to be bad - try this one
     */
    function shuffle(a) {
        let t, j, i = a.length,
            rand = Math.random;

        // For each element in the array, swap it with a random element (which might be itself)
        while (i--) {
            k = rand() * (i + 1) | 0;
            t = a[k];
            a[k] = a[i];
            a[i] = t;
        }
        return a;
    }
});