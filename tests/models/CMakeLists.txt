add_subdirectory( base )

#Style
add_midas_style_test( StyleRodentModelsBase ${CMAKE_SOURCE_DIR}/modules/rodent/models/base/ )
add_midas_style_test( StyleRodentModelsPdo ${CMAKE_SOURCE_DIR}/modules/rodent/models/pdo/ )
add_midas_style_test( StyleRodentModelsDao ${CMAKE_SOURCE_DIR}/modules/rodent/models/dao/ )
