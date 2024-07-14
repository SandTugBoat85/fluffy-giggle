# Fluffy Giggle
Fluffy Giggle is a tool designed to sync agent status between Timetastic and HaloPSA. This allows for synchronization of agent availability from TimeTasic to HTasSA.

## Features

- **Sync Agent Status:** Automatically syncs agent status from Timetastic to HaloPSA.
- **Real-time Updates:** Ensures that any changes in Timetastic are reflected immediately in HaloPSA.
- **Simple Configuration:** Easy to configure and deploy.

## Installation

1. **Clone the Repository:**
    ```sh
    git clone https://github.com/SandTugBoat85/fluffy-giggle.git
    cd fluffy-giggle
    ```

2. **Dependencies:**
    Ensure you have PHP installed on your system. No additional dependencies are required.

## Configuration
1. **Edit Configuration Files:**

    Update the configuration files located in the 'config' directory to match your Timetastic and HaloPSA settings.

2. **Mapping:**

    Configure the mappings in the mappings directory to align leave statuses and user/agent IDs between Timetastic and HaloPSA.

## Usage
Run the Sync Script:

```sh
php sync-status.php
```

Scheduling:

For continuous synchronization, consider setting up a cron job or a scheduled task to run the script at your desired intervals.

## Known issues
1. If TimeTastic returns > 99 entries then only the first 99 are processed (This was thrown together for a 35 user busines)
2. ~~If a user is recorded in TimeTastic for 1/2 a day then they are treated as if they are off for the whole day.~~
3. All agents are marked off with status 5 in HaloPSA

## Contribution
Contributions are welcome! Please follow these steps:

1. Fork the repository.
2. Create a new branch (`git checkout -b feature-branch`).
3. Commit your changes (`git commit -am 'Add new feature'`).
4. Push to the branch (`git push origin feature-branch`).
5. Create a new Pull Request.

## License
This project is licensed under the MIT License. See the LICENSE file for details.

## Contact
For any questions or issues, please open an issue in the repository.

Note: This project is not affiliated with [Timetastic](https://timetastic.co.uk/) or [HaloPSA](https://halopsa.com/). It is an independent tool thown together by someone that was asked to to facilitate the sync of status' between the two platforms **manually** each morning.