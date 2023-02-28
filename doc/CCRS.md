

# Data Sync


sequenceDiagram title Object DataFlow
    App->>+BONG:  Object/Create (100)
    BONG->>+CCRS: Object/INSERT (100->102)
    CCRS-->>BONG: May or May Not Get Response?
    BONG->>CCRS:  Object/INSERT (102->200)
    CCRS-->>BONG: Duplicate (200) or Error (400) 
    BONG->>CCRS:  Object/UPDATE (200->202)
    BONG->>App:   Object 200

