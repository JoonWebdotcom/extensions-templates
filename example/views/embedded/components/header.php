    <style>
/* 1. Main Layout: Sidebar and Content */
.app-container {
    display: flex;
    min-height: 100vh;
    position: relative;
}

/* 2. Sidebar Styling (Left Column) */
.sidebar {
    width: 30%; /* Adjust width as needed */
    background-color: #ffffff;
    padding-top: 20px;
    box-shadow: 2px 0 5px rgba(0, 0, 0, 0.05);
    position: sticky;
    height: 100vh;
    top:20px;
}

.logo {
    font-size: 20px;
    font-weight: bold;
    color: #333;
    padding: 0 20px 20px;
    border-bottom: 1px solid #eee;
}

.nav-menu {
    list-style: none;
    padding: 0;
    margin: 0;
    display: flex;
    flex-direction: column;
    gap:0px;
}

.nav-menu li.active > a{
    background-color: #edf5e1ff; /* Light blue background for active item */
    color: #779d44; /* Blue text color */
    border-left: 3px solid #82af46ff;
    font-weight: bold;
}

.nav-menu li a {
    display: flex;
    align-items: center;
    border-left: 3px solid transparent;
    padding: 10px 10px;
    text-decoration: none;
    color: #555;
    transition: background-color 0.2s;
    width:100%;
    align-items: center;
    display: flex;
    justify-content: flex-start;
    border-bottom: 1px solid #f0f0f0;
    gap:6px;
}

/* 3. Main Content Area (Right Column) */
.main-content {
    flex-grow: 1;
    padding: 0px 0px 0px 20px;
}

/* 4. Automation Section (The main focus area) */
.automation-section {
    background-color: #ffffff;
    padding: 30px;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
    margin-bottom: 30px;
}

.automation-section h2 {
    font-size: 28px;
    font-weight: 300; /* Lighter font weight for the title */
    color: #333;
}

.automation-section .highlight {
    color: #1890ff; /* Blue color for 'boost revenue' */
    font-weight: bold;
}

.automation-section ol {
    padding-left: 20px;
    margin: 20px 0;
}

.automation-section li a{
    color: #779d44;
    text-decoration: none;
}
.automation-section li a:hover {
    color: #ffffffff;
    text-decoration: none;
}

/* Authkey Input Styling */
.authkey-label {
    font-weight: bold;
    margin-top: 20px;
    margin-bottom: 8px;
}

.authkey-input-group {
    display: flex;
    gap: 10px;
    margin-bottom: 10px;
}

.authkey-input {
    flex-grow: 1;
    padding: 10px 15px;
    border: 1px solid #ccc;
    border-radius: 4px;
    font-size: 16px;
    max-width: 400px; /* Constraint input width */
}

.save-button {
    padding: 10px 20px;
    background-color: #1890ff;
    color: white;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-size: 16px;
    font-weight: bold;
}

.help-link {
    display: block;
    color: #1890ff;
    text-decoration: none;
    margin-top: 5px;
}

.explore-links {
    margin-top: 50px;
    font-size: 14px;
    color: #777;
}

/* (You would also style stat-grid, welcome-card, etc., here) */
</style>

  <div class="sidebar">
    <div class="logo"><img src="https://unicorn-images.b-cdn.net/fd22de22-b00f-495c-bc0e-36d28dd817e6?optimizer=gif&width=160" /></div>
      <nav>
        <ul class="nav-menu">
            <?php 
            if (!$apidata): ?>
            <li class="<?php echo ($page ?? '') === 'dashboard' ? 'active' : ''; ?>"><a href="?page=dashboard" ><svg fill="#779d44" height="28px" viewBox="0 0 32 32" xmlns="http://www.w3.org/2000/svg" stroke="#779d44"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"><path d="M27 18.039L16 9.501 5 18.039V14.56l11-8.54 11 8.538v3.481zm-2.75-.31v8.251h-5.5v-5.5h-5.5v5.5h-5.5v-8.25L16 11.543l8.25 6.186z"></path></g></svg> Get Started</a></li>
            
            <?php else: ?>
            <li class="<?php echo ($page ?? '') === 'dashboard' ? 'active' : ''; ?>"><a href="?page=dashboard" ><svg fill="#779d44" height="28px" viewBox="0 0 32 32" xmlns="http://www.w3.org/2000/svg" stroke="#779d44"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"><path d="M27 18.039L16 9.501 5 18.039V14.56l11-8.54 11 8.538v3.481zm-2.75-.31v8.251h-5.5v-5.5h-5.5v5.5h-5.5v-8.25L16 11.543l8.25 6.186z"></path></g></svg> Dashboard</a></li>
            <li class="<?php echo ($page ?? '') === 'automation' ? 'active' : ''; ?>"><a href="?page=automation" > <svg  height="28px" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <path d="M5.06152 12C5.55362 8.05369 8.92001 5 12.9996 5C17.4179 5 20.9996 8.58172 20.9996 13C20.9996 17.4183 17.4179 21 12.9996 21H8M13 13V9M11 3H15M3 15H8M5 18H10" stroke="#779d44" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path> </g></svg> Automation</a></li>
            <li class="<?php echo ($page ?? '') === 'settings' ? 'active' : ''; ?>"><a href="?page=settings"><svg height="28px" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <path d="M20.1 9.2214C18.29 9.2214 17.55 7.9414 18.45 6.3714C18.97 5.4614 18.66 4.3014 17.75 3.7814L16.02 2.7914C15.23 2.3214 14.21 2.6014 13.74 3.3914L13.63 3.5814C12.73 5.1514 11.25 5.1514 10.34 3.5814L10.23 3.3914C9.78 2.6014 8.76 2.3214 7.97 2.7914L6.24 3.7814C5.33 4.3014 5.02 5.4714 5.54 6.3814C6.45 7.9414 5.71 9.2214 3.9 9.2214C2.86 9.2214 2 10.0714 2 11.1214V12.8814C2 13.9214 2.85 14.7814 3.9 14.7814C5.71 14.7814 6.45 16.0614 5.54 17.6314C5.02 18.5414 5.33 19.7014 6.24 20.2214L7.97 21.2114C8.76 21.6814 9.78 21.4014 10.25 20.6114L10.36 20.4214C11.26 18.8514 12.74 18.8514 13.65 20.4214L13.76 20.6114C14.23 21.4014 15.25 21.6814 16.04 21.2114L17.77 20.2214C18.68 19.7014 18.99 18.5314 18.47 17.6314C17.56 16.0614 18.3 14.7814 20.11 14.7814C21.15 14.7814 22.01 13.9314 22.01 12.8814V11.1214C22 10.0814 21.15 9.2214 20.1 9.2214ZM12 15.2514C10.21 15.2514 8.75 13.7914 8.75 12.0014C8.75 10.2114 10.21 8.7514 12 8.7514C13.79 8.7514 15.25 10.2114 15.25 12.0014C15.25 13.7914 13.79 15.2514 12 15.2514Z" fill="#779d44"></path> </g></svg> Settings</a></li>
            <?php endif; ?>
        </ul>
    </nav>
</div>