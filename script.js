// Redirect to Start Campaign Page
function startCampaign() {
    window.location.href = "start_campaign.html";
}

// Edit Personal Details Function
function editProfile() {
    alert("Redirecting to Edit Profile Page...");
    window.location.href = "edit_profile.html";
}

// Sample Campaigns Data
const campaigns = [
    { title: "Medical Aid for John", description: "Help John recover from a major surgery." },
    { title: "Education Fund for Kids", description: "Supporting children's education in remote areas." },
    { title: "Disaster Relief Support", description: "Providing aid to flood victims." }
];

// Dynamically Load Campaigns
function loadCampaigns() {
    let campaignsList = document.getElementById("campaigns-list");

    campaigns.forEach((campaign) => {
        let campaignCard = `
            <div class="col-md-4">
                <div class="campaign-card">
                    <h4>${campaign.title}</h4>
                    <p>${campaign.description}</p>
                </div>
            </div>
        `;
        campaignsList.innerHTML += campaignCard;
    });
}

// Load campaigns on page load
document.addEventListener("DOMContentLoaded", loadCampaigns);
