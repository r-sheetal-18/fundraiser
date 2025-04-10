// $(document).ready(function () {
//     $.ajax({
//         url: "user_info.php",
//         method: "GET",
//         dataType: "json",
//         success: function (data) {
//             if (data.error) {
//                 console.error("User not logged in.");
//             } else {
//                 $("#profile-img").attr("src", data.profile_image);
//                 $("#username").text("Welcome, " + data.username);
//                 $("#user-welcome").text(data.username);
//             }
//         },
//         error: function () {
//             console.error("Failed to fetch user data.");
//         }
//     });
// });
$(document).ready(function () {
    // Fetch User Info
    $.ajax({
        url: "user_info.php",
        method: "GET",
        dataType: "json",
        success: function (data) {
            if (data.error) {
                console.error("User not logged in.");
            } else {
                $("#profile-img").attr("src", data.profile_image);
                $("#full-name").text(data.full_name);
                $("#user-welcome").text(data.full_name);
            }
        },
        error: function () {
            console.error("Failed to fetch user data.");
        }
    });

    // Fetch Approved Campaigns
    $.ajax({
        url: "fetch_campaigns.php",
        method: "GET",
        dataType: "json",
        success: function (data) {
            let campaignsList = $("#campaigns-list");

            if (data.length === 0) {
                campaignsList.html("<p class='text-center text-light'>No approved campaigns found.</p>");
                return;
            }

            data.forEach(campaign => {
                let progress = (campaign.raised_amount / campaign.goal_amount) * 100;

                let campaignCard = `
                    <div class="col-md-4">
                        <div class="campaign-card p-3">
                            <h4 class="text-primary">${campaign.title}</h4>
                            <p class="text-light">${campaign.description.substring(0, 100)}...</p>
                            <p><strong>Goal:</strong> ₹${campaign.goal_amount.toLocaleString()} | 
                               <strong>Raised:</strong> ₹${campaign.raised_amount.toLocaleString()}</p>
                            <div class="progress mb-2">
                                <div class="progress-bar bg-success" role="progressbar" style="width: ${progress}%" 
                                     aria-valuenow="${progress}" aria-valuemin="0" aria-valuemax="100">
                                </div>
                            </div>
                            <a href="campdetails.php?id=<?= $row['campaign_id'] ?>&ref=user" class="btn btn-outline-primary btn-sm">View Details</a>

                        </div>
                    </div>
                `;

                campaignsList.append(campaignCard);
            });
        },
        error: function () {
            console.error("Failed to fetch campaigns.");
        }
    });
});
