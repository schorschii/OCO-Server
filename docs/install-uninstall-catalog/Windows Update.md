# Install Windows Updates
In enterprise environments, it it often not a good idea to let Windows clients and servers install updates automatically on the first day of release because of the bad update quality from Microsoft in the last years. A better idea is to have a patch management workflow in which you roll out a new update on a small group of test computers. If everything is fine with them, you roll out the patch for all computers. In the past, admins did this using WSUS (Windows Server Update Services) from Microsoft. It always had a terrible user interface and reporting section, but now Microsoft deprecated the entire WSUS project - use OCO instead to roll out Windows updates. No need to pay for Intune to have a Windows patch management!

Download your patch from the [Microsoft Update Catalog](https://www.catalog.update.microsoft.com).

Install it with:
```
dism /online /add-package /packagepath:FILENAME.cab
```

Or even better: use the [Paketeer](https://github.com/schorschii/OCO-Server-Extensions/tree/master/paketeer) extension!
