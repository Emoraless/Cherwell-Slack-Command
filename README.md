The program integrates Cherwell ITSM with Slack by using their custom slash commands. Cherwell command provides efficiency to a team of technicians attempting to solve and commumicate about customer issues. It eliminates the need for technicians to follow the process of redirecting to Cherwell, logging in, searching up the issues that needs assistance and waiting for everything to load to be able to navigate and provide information or resolve. 

The program works with slack in four different ways depending on the type of ticket the technician would like to display on slack.
/cherwell I ##### for an incident
/cherwell T ##### for a task
/cherwell C ##### for a change request
/cherwell P ##### for a problem

The technician looking for assistance will begin the post with /cherwell. Then they will specify which type of issue they would like to have displayed on the slack channel. Lastly, they will provide a valid number for the type of issue they would like displayed. If the incident is invalid, it will notify them that the ticket was not found. If the ticket is found it will organize the incident by providing the issue type and number, followed by whoThen it was requested by and also the team it was owned by. Following would be a short description and then a long detailed description of the issue requested. 
